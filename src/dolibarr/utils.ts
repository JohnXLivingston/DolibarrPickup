import { setPrintLabelUrl } from '../shared/printlabel'

let baseUrl: string | undefined
function setBaseUrl (url: string): void {
  baseUrl = url
  console.log('Base url is set to:', url)
  setPrintLabelUrl(getUrl('pickup_printable_label.php'))
}

function getUrl (path: string, parameters?: {[key: string]: string}): string {
  if (!baseUrl) {
    throw new Error('DolibarrPickup base url is not set')
  }
  const uri = new URL(path, document.location.origin + baseUrl)
  if (parameters === null) {
    return uri.toString()
  }
  for (const k in parameters) {
    const v = parameters[k]
    uri.searchParams.set(k, v)
  }
  return uri.toString()
}

export {
  setBaseUrl,
  getUrl
}
