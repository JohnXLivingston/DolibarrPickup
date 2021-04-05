let current: JQuery | null
function waitingOn (): void {
  const el = $('[pickupmobileapp-container]')
  if (el.length) {
    current = el
    el.addClass('pickupmobile-waiting')
  }
}

function waitingOff (): void {
  if (current) {
    current.removeClass('pickupmobile-waiting')
  }
}

export {
  waitingOn,
  waitingOff
}
