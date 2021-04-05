import { translate } from './translate'

const nunjucks = require('../../node_modules/nunjucks/browser/nunjucks.min')
require('../../build/templates/templates.js')

function initNunjucks () {
  nunjucks.configure({ autoescape: true })
}

function commonNunjucksVars () {
  return {
    translate
  }
}

export {
  nunjucks,
  initNunjucks,
  commonNunjucksVars
}
