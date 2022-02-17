import type { NunjucksVars } from '../nunjucks'
import type { ResultData } from '../data'
import { Stack } from '../stack'
import { RenderReason } from '../constants'
import { Veto } from '../veto'

interface StateDefinitionBase {
  label: string
}

type StateRetrievedData = Map<string, ResultData | false> // false means «missing key»

abstract class State {
  readonly type: string
  readonly label: string

  constructor (type: string, definition: StateDefinitionBase) {
    this.type = type
    this.label = definition.label
  }

  retrieveData (_stack: Stack, _force: boolean): StateRetrievedData {
    return new Map<string, ResultData>()
  }

  renderVars (stack: Stack, retrievedData: StateRetrievedData): NunjucksVars {
    let isMissingKey = false
    let isError = false
    let isPending = false
    retrievedData.forEach((result) => {
      if (result === false) {
        isMissingKey = true
      } else if (result.status === 'rejected') {
        isError = true
      } else if (result.status === 'pending') {
        isPending = true
      }
    })

    if (!isMissingKey && !isError && isPending) {
      setTimeout(() => {
        const div = $('[pickupmobile-pending]')
        if (div.length) {
          div.trigger('rerender-state')
        } else {
          console.log('The pending div is not in the dom anymore.')
        }
      }, 500)
    }

    const vars = {
      retrievedData: {
        map: retrievedData,
        isMissingKey,
        isError,
        isPending
      }
    }

    if (!isError && !isPending) {
      this._renderVars(stack, retrievedData, vars)
    }

    return vars
  }

  _renderVars (_stack: Stack, _retrievedData: StateRetrievedData, _vars: NunjucksVars): void {}

  /**
   * Before calling renderVars a call of renderVeto1 is made.
   * You can then return instruction to skip to another state.
   * @param reason
   * @param stack
   */
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  renderVeto1 (reason: RenderReason, stack: Stack): Veto | undefined {
    return undefined
  }

  /**
   * After calling renderVars, and before passing the result
   * to the template, a call of renderVeto2 is made.
   * You can then return instruction to skip to another state.
   * @param reason
   * @param stack
   * @param vars
   */
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  renderVeto2 (reason: RenderReason, stack: Stack, vars: any): Veto | undefined {
    return undefined
  }

  abstract bindEvents (dom: JQuery, stack: Stack): void

  postRenderAndBind (_dom: JQuery, _stack: Stack, _bind: boolean, _vars: any): void {}

  // unbindEvents (dom: JQuery): void {
  //   dom.off('.stateEvents')
  // }

  /**
   * List states that can be reached by this one. Used for debugging (searching missing states)
   */
  abstract possibleGotos (): Promise<string[]>

  /**
   * List states that must be found preceding this one (for example with StateSave.saveUntil).
   * Used for debugging.
   */
  async possibleBackwards (): Promise<string[]> {
    return []
  }

  /**
   * List nunjucks strings used by this template.
   * Can return an example vars object to pass to nunjucks render method.
   */
  async possibleNunjucks (): Promise<PossibleNunjucks[]> {
    return []
  }
}

interface PossibleNunjucks {
  format: string
  var?: NunjucksVars
}

export {
  State,
  StateDefinitionBase,
  StateRetrievedData,
  PossibleNunjucks
}
