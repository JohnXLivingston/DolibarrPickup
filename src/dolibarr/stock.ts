import { createGenerateBatchNumberButton } from './batch'

function enhanceStockTransferForm (_apiUrl: string, productId: string, batchIsUnique: boolean): void {
  console.log('Entering enhanceStockTransferForm...')
  document.querySelectorAll('input[name=batch_number]:not(disabled)').forEach((el) => {
    console.log('enhanceStockTransferForm: found a batch_number input')
    const input: HTMLInputElement = el as HTMLInputElement
    if (input.value) {
      console.log('enhanceStockTransferForm: input is not empty, skipping')
      return
    }
    createGenerateBatchNumberButton(input, productId)
  })

  if (batchIsUnique) {
    console.log('enhanceStockTransferForm: batch must be unique, settings nbpiece to 1 by default')
    document.querySelectorAll('input[name=nbpiece]:not(disabled)').forEach((el) => {
      if (!('value' in (el as any))) { return }
      if (!(el as HTMLInputElement).value) {
        (el as HTMLInputElement).value = '1'
      }
    })
  }
}

export {
  enhanceStockTransferForm
}
