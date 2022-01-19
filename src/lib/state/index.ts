import { State } from './state'
import { StateCompute, StateComputeDefinition } from './compute'
import { StateChoice, StateChoiceDefinition } from './choice'
import { StateForm, StateFormDefinition } from './form'
import { StatePick, StatePickDefinition } from './pick'
import { StateSave, StateSaveDefinition } from './save'
import { StateSelect, StateSelectDefinition } from './select'
import { StateShow, StateShowDefinition } from './show'
import { StateUnknown } from './unknown'

export * from './compute'
export * from './choice'
export * from './form'
export * from './pick'
export * from './save'
export * from './select'
export * from './state'
export * from './unknown'

type StateDefinition = StateChoiceDefinition | StateFormDefinition | StatePickDefinition | StateSaveDefinition | StateSelectDefinition | StateComputeDefinition | StateShowDefinition

function createState (o: any): State {
  if (typeof o !== 'object') {
    throw new Error('Wrong parameter, should be an object')
  }
  const type: string = o.type ?? ''
  switch (type) {
    case 'choice': return new StateChoice(o)
    case 'form': return new StateForm(o)
    case 'pick': return new StatePick(o)
    case 'save': return new StateSave(o)
    case 'select': return new StateSelect(o)
    case 'show': return new StateShow(o)
    case 'compute': return new StateCompute(o)
    case 'unknown': return new StateUnknown()
  }

  throw new Error(`Unknown type: ${o.type as string}`)
}

export {
  createState,
  StateDefinition
}
