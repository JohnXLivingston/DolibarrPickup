import { translate } from './translate'

// eslint-disable-next-line @typescript-eslint/no-var-requires
const nunjucks = require('../../node_modules/nunjucks/browser/nunjucks.min')
require('../../build/templates/templates.js')

interface NunjucksVars { [key: string]: any }

function initNunjucks (): void {
  nunjucks.configure({ autoescape: true })
}

function commonNunjucksVars (): NunjucksVars {
  return {
    translate
  }
}

export {
  nunjucks,
  initNunjucks,
  commonNunjucksVars,
  NunjucksVars
}
