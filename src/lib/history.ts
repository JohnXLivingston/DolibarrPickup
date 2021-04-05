function initHistory (): void {
  console.info('Pushing a state so we can manage history back.')
  history.pushState({}, '')
  window.onpopstate = function () {
    const previousButton = $('[pickupmobile-goto-previous]')
    if (previousButton.length) {
      console.info('There is a previous button in the DOM, using it to handle popstate')
      history.pushState({}, '')
      previousButton.click()
    } else {
      console.info('The stack is empty, leaving pickupmobileapp')
      history.back()
    }
  }
}

export {
  initHistory
}
