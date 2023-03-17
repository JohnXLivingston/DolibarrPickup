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

function printLabelIconSVG (): string {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="64" height="16" fill="currentColor" viewBox="0 0 64 16">
  <g id="bars" fill="currentColor" stroke="none">
    <rect x="0" y="0" width="4" height="30"></rect>
    <rect x="6" y="0" width="2" height="30"></rect>
    <rect x="12" y="0" width="2" height="30"></rect>
    <rect x="22" y="0" width="4" height="30"></rect>
    <rect x="28" y="0" width="6" height="30"></rect>
    <rect x="36" y="0" width="2" height="30"></rect>
    <rect x="44" y="0" width="2" height="30"></rect>
    <rect x="48" y="0" width="6" height="30"></rect>
    <rect x="60" y="0" width="4" height="30"></rect>
    <rect x="66" y="0" width="2" height="30"></rect>
    <rect x="70" y="0" width="6" height="30"></rect>
    <rect x="78" y="0" width="8" height="30"></rect>
    <rect x="88" y="0" width="4" height="30"></rect>
    <rect x="96" y="0" width="4" height="30"></rect>
  </g>
</svg>`
  // return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-qr-code" viewBox="0 0 16 16">
  //   <path d="M2 2h2v2H2V2Z"/>
  //   <path d="M6 0v6H0V0h6ZM5 1H1v4h4V1ZM4 12H2v2h2v-2Z"/>
  //   <path d="M6 10v6H0v-6h6Zm-5 1v4h4v-4H1Zm11-9h2v2h-2V2Z"/>
  //   <path d="M10 0v6h6V0h-6Zm5 1v4h-4V1h4ZM8 1V0h1v2H8v2H7V1h1Zm0 5V4h1v2H8ZM6 8V7h1V6h1v2h1V7h5v1h-4v1H7V8H6Zm0 0v1H2V8H1v1H0V7h3v1h3Zm10 1h-1V7h1v2Zm-1 0h-1v2h2v-1h-1V9Zm-4 0h2v1h-1v1h-1V9Zm2 3v-1h-1v1h-1v1H9v1h3v-2h1Zm0 0h3v1h-2v1h-1v-2Zm-4-1v1h1v-2H7v1h2Z"/>
  //   <path d="M7 12h1v3h4v1H7v-4Zm9 2v2h-3v-1h2v-1h1Z"/>
  // </svg>`
}

export {
  setPrintLabelUrl,
  printProductLabel,
  printPickupLabels,
  printPickupLineLabels,
  printLabelIconSVG
}
