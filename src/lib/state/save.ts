import type { NunjucksVars } from '../nunjucks'
import { RenderReason } from '../constants'
import { State, StateDefinitionBase, StateRetrievedData } from './state'
import { Stack, RemoveBetween } from '../stack'
import { setData } from '../data'
import { waitingOn, waitingOff } from '../waiting'
import { Veto } from '../veto'

interface StateSaveDefinition extends StateDefinitionBase {
  type: 'save'
  saveUntil: string
  removeUntil?: string
  removeFromStack?: boolean
  key: string
  primaryKey: string
  labelKey: string
  goto: string
  dependingCacheKey?: string
}

class StateSave extends State {
  private readonly saveUntil: string
  private readonly removeUntil: string
  private readonly removeFromStack: boolean
  private readonly key: string
  private readonly primaryKey: string
  private readonly labelKey: string
  private readonly goto: string
  private readonly dependingCacheKey?: string

  constructor (definition: StateSaveDefinition) {
    super('save', definition)
    this.saveUntil = definition.saveUntil
    this.removeUntil = definition.removeUntil ?? definition.saveUntil
    this.removeFromStack = !!definition.removeFromStack
    this.key = definition.key
    this.primaryKey = definition.primaryKey
    this.labelKey = definition.labelKey
    this.goto = definition.goto
    this.dependingCacheKey = definition.dependingCacheKey
  }

  _renderVars (stack: Stack, retrievedData: StateRetrievedData, h: NunjucksVars): void {
    h.stackValues = stack.previous?.getStackValuesUntil(this.saveUntil)
    h.displayStackValue = Stack.displayStackValue
    h.getDisplayStackValue = Stack.getDisplayStackValue
  }

  renderVeto1 (reason: RenderReason, stack: Stack): Veto | undefined {
    const parentVeto = super.renderVeto1(reason, stack)
    if (parentVeto) return parentVeto

    if (reason === RenderReason.GOING_BACKWARD) {
      // Cant go back to a save state... it dropped all data coming after removeUntil
      return {
        type: 'backward'
      }
    }
    return undefined
  }

  bindEvents (dom: JQuery, stack: Stack): void {
    dom.on('submit.stateEvents', 'form', (ev) => {
      const form = $(ev.currentTarget)
      if (form.hasClass('pickupmobile-saving')) {
        console.log('Already submitting... Canceling.')
        return
      }
      console.log('Saving data...')
      $('[pickupmobile-save-error-container]').text('').addClass('d-none')
      form.addClass('pickupmobile-saving')
      waitingOn()

      const ok = (result: any): void => {
        const id = result[this.primaryKey]
        console.log('Reading save result...')
        if (!id || Number(id) <= 0) {
          notOk('Missing primary key in result')
          return
        }
        console.log('We got a new object with primaryKey: ' + (id as string))
        form.removeClass('pickupmobile-saving')
        waitingOff()
        stack.setValues({
          label: this.key, // FIXME: something else
          name: this.key, // FIXME: something else. Do not forget to change in the call of getStackValue below.
          value: id,
          display: result[this.labelKey]
        })
        const removeBetween: RemoveBetween = {
          from: this.removeFromStack ? undefined : stack.stateName,
          to: this.removeUntil
        }
        dom.trigger('goto-state', [this.goto, removeBetween])
      }
      const notOk = (err: any): void => {
        console.error(err)
        form.removeClass('pickupmobile-saving')
        waitingOff()
        let txt: string
        if (typeof err === 'string') {
          txt = err
        } else if (typeof err === 'object' && ('statusText' in err)) {
          txt = err.statusText
        } else {
          txt = '' + (err as string)
        }
        $('[pickupmobile-save-error-container]').text('Error: ' + txt).removeClass('d-none')
      }

      // If the object was already saved... Do not save again!
      const previousSV = stack.getStackValue(this.key) // FIXME: it is not this.key.
      if (previousSV?.value) {
        console.log('The value was already saved.')
        form.removeClass('pickupmobile-saving')
        waitingOff()
        dom.trigger('goto-state', [this.goto])
        return
      }

      const sva = stack.getStackValuesUntil(this.saveUntil)
      if (!sva) {
        notOk('Error in configuration, cant find state ' + this.saveUntil)
        return
      }
      let data
      try {
        data = Stack.stackValuesToParams(sva)
      } catch (err) {
        notOk(err)
        return
      }

      const p = setData(this.key, data, this.dependingCacheKey)
      p.then(
        ok,
        notOk
      )
    })
  }

  async possibleGotos (): Promise<string[]> {
    return [this.goto]
  }

  async possibleBackwards (): Promise<string[]> {
    const r = [this.saveUntil]
    if (this.removeUntil !== this.saveUntil) {
      r.push(this.removeUntil)
    }
    return r
  }
}

export {
  StateSave,
  StateSaveDefinition
}
