let printLabelUrl: string = '/custom/pickup/pickup_printable_label.php'

function setPrintLabelUrl (url: string): void {
  printLabelUrl = url
}

function _print (dom: JQuery | HTMLElement, url: string): void {
  const iframe: HTMLIFrameElement = document.createElement('iframe')
  iframe.setAttribute('src', url)
  iframe.style.display = 'none'
  dom.after(iframe)

  iframe.onload = () => {
    iframe.contentWindow?.print()
  }
}

function printProductLabel (dom: JQuery | HTMLElement, id: string): void {
  const url = new URL(printLabelUrl, window.location.origin)
  url.searchParams.set('what', 'product')
  url.searchParams.set('product_id', id)

  _print(dom, url.toString())
}

function printPickupLabels (): void {
  throw new Error('not implemented yet')
}

function printPickupLineLabels (): void {
  throw new Error('not implemented yet')
}

export {
  setPrintLabelUrl,
  printProductLabel,
  printPickupLabels,
  printPickupLineLabels
}
