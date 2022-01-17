// TODO: this is only a dirty quick translation library.
import { fr } from '../translations/fr'

let lang: string = 'en'
function initLang () {
  lang = $('html').attr('lang') || 'en'
}

function translate (s: string, data?: {[key: string]: string}): string {
  let t: string = s
  if (lang.startsWith('fr')) {
    if (s in fr) t = fr[s]
  }
  if (data) {
    for (const key in data) {
      const r = new RegExp('\\{' + key + '\\}', 'gi')
      t = t.replace(r, data[key])
    }
  }
  return t
}

export {
  initLang,
  translate
}
