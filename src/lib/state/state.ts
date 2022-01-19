import type { NunjucksVars } from '../nunjucks'
import { Stack } from '../stack'
import { RenderReason } from '../constants'
import { Veto } from '../veto'

interface StateDefinitionBase {
  label: string
}

abstract class State {
  readonly type: string
  readonly label: string

  constructor (type: string, definition: StateDefinitionBase) {
    this.type = type
    this.label = definition.label
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  renderVars (stack: Stack): NunjucksVars {
    return {}
  }

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
  PossibleNunjucks
}
