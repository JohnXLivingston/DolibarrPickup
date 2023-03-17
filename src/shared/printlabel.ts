let printLabelUrl: string = '/custom/pickup/pickup_printable_label.php'

function setPrintLabelUrl (url: string): void {
  printLabelUrl = url
}

function _print (dom: JQuery | HTMLElement, url: string): void {
  const domElement: HTMLElement | undefined = ('get' in dom) ? dom.get(0) : dom
  if (!domElement) { return }

  const iframe: HTMLIFrameElement = document.createElement('iframe')
  iframe.setAttribute('src', url)
  iframe.style.display = 'none'

  let waitingElement: HTMLElement | undefined
  if (domElement.classList.contains('btn')) {
    // It seems we are using a Boostrap button (mobile app), so we can use Bootstrap spinner element
    waitingElement = document.createElement('span')
    waitingElement.classList.add('spinner-border')
    waitingElement.classList.add('spinner-border-sm')
    dom.prepend(waitingElement)
  }

  domElement.after(iframe)

  iframe.onload = () => {
    iframe.contentWindow?.print()
    waitingElement?.remove()
  }
}

function printProductLabel (dom: JQuery | HTMLElement, id: string): void {
  const url = new URL(printLabelUrl, window.location.origin)
  url.searchParams.set('what', 'product')
  url.searchParams.set('product_id[]', id)

  _print(dom, url.toString())
}

function printPickupLabels (dom: JQuery | HTMLElement, id: string): void {
  const url = new URL(printLabelUrl, window.location.origin)
  url.searchParams.set('what', 'pickup')
  url.searchParams.set('pickup_id[]', id)

  _print(dom, url.toString())
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
