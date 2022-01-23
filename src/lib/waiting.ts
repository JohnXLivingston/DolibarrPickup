function waitingOn (): void {
  const submits = document.querySelectorAll('input[type=submit]')
  submits.forEach((submit) => {
    submit.setAttribute('pickupmobile-disabled-waiting', '')
    if ('disabled' in submit) {
      (submit as HTMLInputElement).disabled = true
    }
  })
}

function waitingOff (): void {
  const submits = document.querySelectorAll('input[type=submit][pickupmobile-disabled-waiting]')
  submits.forEach((submit) => {
    submit.removeAttribute('pickupmobile-disabled-waiting')
    if ('disabled' in submit) {
      (submit as HTMLInputElement).disabled = false
    }
  })
}

export {
  waitingOn,
  waitingOff
}
