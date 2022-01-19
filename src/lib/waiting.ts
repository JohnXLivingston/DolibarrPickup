let current: JQuery | null
function waitingOn (): void {
  const el = $('[pickupmobileapp-container]')
  if (el.length > 0) {
    current = el
    el.addClass('pickupmobile-waiting')
  }
}

function waitingOff (): void {
  current?.removeClass('pickupmobile-waiting')
}

export {
  waitingOn,
  waitingOff
}
