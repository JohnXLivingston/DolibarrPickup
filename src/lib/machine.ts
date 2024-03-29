import { Stack } from './stack'
import { createState, State, StateUnknown, StateDefinition } from './state/index'
import { RenderReason } from './constants'
import { nunjucks, commonNunjucksVars } from './nunjucks'
import type { RemoveBetween } from './stack'

class Machine {
  private readonly name: string
  private readonly clearStackOnGotoInit: boolean
  private readonly version: string
  private readonly content: JQuery
  private readonly states: {[key: string]: State}
  private stack: Stack
  private readonly userId: string
  private readonly dolibarrUrl?: string

  constructor (
    name: string, version: string,
    userId: string,
    clearStackOnGotoInit: boolean,
    definition: {[key: string]: StateDefinition},
    dolibarrUrl?: string
  ) {
    this.name = name
    this.clearStackOnGotoInit = clearStackOnGotoInit
    this.version = version
    this.userId = userId
    this.dolibarrUrl = dolibarrUrl
    this.states = {}
    if (typeof definition !== 'object') {
      throw new Error('Invalid definition')
    }
    for (const stateName in definition) {
      this.states[stateName] = createState(definition[stateName])
    }
    if (!('init' in this.states)) {
      throw new Error('Missing init state in machine definition')
    }
    if (!('???' in this.states)) {
      this.states['???'] = new StateUnknown()
    }
    if (!(this.states['???'] instanceof StateUnknown)) {
      throw new Error('Definition constains an invalid entry for "???"')
    }

    this.content = $('<div style="height: 100%"></div>')

    const currentLocalStorageKey = this.stackStoragePrefix() + this.name
    const stackSerialized = localStorage.getItem(currentLocalStorageKey)
    if (stackSerialized) {
      try {
        this.stack = Stack.deserialize(stackSerialized, version) ?? new Stack('init')
      } catch (err) {
        console.error(err)
        this.stack = new Stack('init')
      }
    } else {
      this.stack = new Stack('init')
    }

    // Cleaning localStorage to remove all deprecated stacks...
    for (let i = localStorage.length - 1; i >= 0; i--) {
      const key = localStorage.key(i)
      if (key?.startsWith('stack_') && key.endsWith(this.name) && key !== currentLocalStorageKey) {
        localStorage.removeItem(key)
      }
    }
  }

  init (container: JQuery): void {
    container.append(this.content)
    this.render(RenderReason.INIT)
  }

  currentState (): State {
    return this.state(this.stack.stateName)
  }

  state (name: string): State {
    if (!(name in this.states)) {
      console.error('Unknown state "' + name + '"')
      return this.states['???']
    }
    return this.states[name]
  }

  render (reason: RenderReason): void {
    this._render(reason, true)
  }

  rerender (reloadData: boolean = false): void {
    this._render(reloadData ? RenderReason.RELOADDATA : RenderReason.REFRESHING, false)
  }

  private _render (reason: RenderReason, bind: boolean): void {
    const currentState: State = this.currentState()

    const veto1 = currentState.renderVeto1(reason, this.stack)
    if (veto1) {
      console.log('There is a renderVeto1', veto1)
      if (veto1.type === 'backward') {
        setTimeout(() => this.gotoPreviousState(), 0)
      } else if (veto1.type === 'forward') {
        setTimeout(() => this.gotoState(veto1.goto), 0)
      }
      return
    }

    const retrievedData = currentState.retrieveData(this.stack, reason === RenderReason.RELOADDATA)
    const vars = Object.assign({}, commonNunjucksVars(), currentState.renderVars(this.stack, retrievedData), {
      stack: this.stack,
      currentState: currentState
    })

    const veto2 = currentState.renderVeto2(reason, this.stack, vars)
    if (veto2) {
      console.log('There is a renderVeto2', veto2)
      if (veto2.type === 'backward') {
        setTimeout(() => this.gotoPreviousState(), 0)
      } else if (veto2.type === 'forward') {
        setTimeout(() => this.gotoState(veto2.goto), 0)
      }
      return
    }

    const s = nunjucks.render('mobile.njk', vars)
    if (bind) this.unbindEvents()
    this.content.html(s)
    this.postRender()
    if (bind) this.bindEvents()
    currentState.postRenderAndBind(this.content, this.stack, bind, vars)
  }

  private unbindEvents (): void {
    // removing all handlers... seems more appropriate and robust than removing only .machinEvents and states' events.
    this.content.off()
    // this.content.off('.machineEvents')
    // const currentState = this.currentState()
    // currentState.unbindEvents(this.content)
  }

  private bindEvents (): void {
    this.content.on('click', '[pickupmobile-reset-stack]', () => {
      this.resetStack()
    })

    this.content.on('click', '[pickupmobile-return-dolibarr]', () => {
      if (this.dolibarrUrl) {
        window.location.href = this.dolibarrUrl
      }
    })

    this.content.on('rerender-state.machineEvents', () => {
      console.log('Asking to render again...')
      this.rerender()
    })

    this.content.on('click', '[pickupmobile-reloaddata]', () => {
      console.log('Asking to reload data...')
      this.rerender(true)
    })

    this.content.on('goto-state.machineEvents', (ev, stateName: string, removeBetween?: RemoveBetween) => {
      if (!stateName || typeof stateName !== 'string') {
        throw new Error('Invalid goto-state event')
      }
      console.log('Going to state: ' + stateName)
      this.gotoState(stateName, removeBetween)
    })

    this.content.on('click.machineEvents', '[pickupmobile-goto-previous]', () => {
      console.log('Going to previous state...')
      this.gotoPreviousState()
    })

    const currentState = this.currentState()
    currentState.bindEvents(this.content, this.stack)

    // Must be after currentState.bindEvents.
    this.content.on('submit.machineEvents', function (ev) {
      console.log('Submitting the form...')
      ev.preventDefault()
      ev.stopImmediatePropagation()
    })
  }

  private postRender (): void {
    this.content.find('[pickupmobile-select2]').each((i, html) => {
      const el = $(html)
      el.removeAttr('pickupmobile-select2')
      el.select2({
        theme: 'bootstrap-5'
      })
    })
  }

  /**
   * @param name state name to go to
   * @param removeBetween if given, removes states from stack between 'from' (excluded) and 'to' (included)
   *  (from is the more recent state).
   *  If there is no from, all states on top of 'to' will be removed
   */
  gotoState (name: string, removeBetween?: RemoveBetween): void {
    let stack: Stack | undefined = this.stack
    if (this.clearStackOnGotoInit && name === 'init') {
      stack = undefined
    } else if (removeBetween) {
      if (removeBetween.from) {
        const from = stack.findByStateName(removeBetween.from)
        if (from) {
          from.previous = from.findByStateName(removeBetween.to)?.previous
        }
      } else {
        stack = stack.findByStateName(removeBetween.to)?.previous
      }
    }
    this.stack = new Stack(name, stack)
    this.saveStack()
    this.render(RenderReason.GOING_FORWARD)
  }

  gotoPreviousState (): void {
    if (!this.stack.previous) {
      throw new Error('Cant go to previous state')
    }
    this.stack = this.stack.previous
    this.saveStack()
    this.render(RenderReason.GOING_BACKWARD)
  }

  resetStack (): void {
    this.stack = new Stack('init')
    this.saveStack()
    this.render(RenderReason.INIT)
  }

  stackStoragePrefix (): string {
    return 'stack_' + this.userId + '_'
  }

  saveStack (): void {
    localStorage.setItem(this.stackStoragePrefix() + this.name, this.stack.serialize(this.version))
  }

  async findMissingStates (name?: string): Promise<string[]> {
    const missings: {[key: string]: true} = {}
    const states: string[] = [name ?? 'init']
    const seen: {[key: string]: true} = {}
    let key: string | undefined
    while (undefined !== (key = states.shift())) {
      if (key in seen) {
        continue
      }
      seen[key] = true

      const state = this.states[key]
      if (!state) {
        missings[key] = true
        continue
      }

      const possibles = (await state.possibleGotos()).concat(await state.possibleBackwards())
      for (let i = 0; i < possibles.length; i++) {
        const possible = possibles[i]
        if (!(possible in this.states)) {
          missings[possible] = true
        } else if (this.states[possible].type === 'unknown') {
          missings[possible] = true
        } else if (!seen[possible]) {
          states.push(possible)
        }
      }
    }
    return Object.keys(missings)
  }

  async findMissingBackwardStates (name?: string): Promise<string[]> {
    const statesToTest: string[] = name ? [name] : Object.keys(this.states)

    // First, building all possible reverse path...
    const map: {[key: string]: string[]} = {}
    for (const key in this.states) {
      const state = this.states[key]
      const possibles = await state.possibleGotos()
      for (let i = 0; i < possibles.length; i++) {
        const possible = possibles[i]
        if (!(possible in map)) {
          map[possible] = []
        }
        if (!map[possible].find(v => v === key)) {
          map[possible].push(key)
        }
      }
    }

    function searchPath (seen: string[], currentKey: string, endingKey: string, depth: number): boolean {
      // console.debug(`searchPath depth=${depth}: currentKey=${currentKey} endingKey=${endingKey} seen.length=${seen.length}`)
      if (endingKey === currentKey) {
        // found!
        return true
      }
      if (seen.find(k => k === currentKey)) {
        // this is a loop... Not sure it is normal, but thats not the point of this method.
        return false
      }
      seen.push(currentKey)

      if (!(currentKey in map)) {
        // This state does not exist... So there is no path here.
        // Note: findMissingStates should find it.
        return false
      }
      for (let i = 0; i < map[currentKey].length; i++) {
        if (searchPath(seen, map[currentKey][i], endingKey, depth + 1)) {
          return true
        }
      }
      return false
    }

    const result: string[] = []
    for (let i = 0; i < statesToTest.length; i++) {
      const key = statesToTest[i]
      const state = this.states[key]
      if (!state) {
        throw new Error(`State ${key} does not exist.`)
      }
      const backwardStates = await state.possibleBackwards()
      for (let j = 0; j < backwardStates.length; j++) {
        const backwardKey = backwardStates[j]
        // searching a path from key to backwardKey...
        if (!searchPath([], key, backwardKey, 0)) {
          result.push(`Cannot go back from ${key} to ${backwardKey}.`)
        }
      }
    }

    return result
  }

  async findOrphans (): Promise<string[]> {
    const existings: {[key: string]: true} = {}
    for (const key in this.states) {
      const state = this.states[key]
      const possibles = await state.possibleGotos()
      for (let i = 0; i < possibles.length; i++) {
        const possible = possibles[i]
        existings[possible] = true
      }
    }
    const result: string[] = []
    for (const key in this.states) {
      if (!existings[key]) result.push(key)
    }
    return result
  }

  async testNunjucks (name?: string): Promise<string[]> {
    const statesToTest: string[] = name ? [name] : Object.keys(this.states)
    const result: string[] = []
    for (let i = 0; i < statesToTest.length; i++) {
      const key = statesToTest[i]
      if (!this.states[key]) throw new Error(`Can't find state ${key}.`)
      const state = this.states[key]
      const njks = await state.possibleNunjucks()
      for (let j = 0; j < njks.length; j++) {
        const njk = njks[j]
        try {
          nunjucks.renderString(njk.format, njk.var ?? {})
        } catch (err) {
          result.push(
            `For state ${key}, there was a format that failed:` +
            '\n==================\n' +
            njk.format +
            '\n==================\n' +
            'With error: ' + (err as string)
          )
        }
      }
    }
    return result
  }

  async findProblems (): Promise<any> {
    return {
      missings: await this.findMissingStates(),
      missingsBackwards: await this.findMissingBackwardStates(),
      nunjucks: await this.testNunjucks(),
      orphans: await this.findOrphans()
    }
  }
}

export {
  Machine
}
