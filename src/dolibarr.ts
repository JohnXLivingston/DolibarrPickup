import { printPickupLabels, printPickupLineLabels, printProductLabel, printProductLotLabel } from './shared/printlabel'
import { enhanceStockTransferForm } from './dolibarr/stock'
import { setBaseUrl } from './dolibarr/utils'

declare global {
  interface Window {
    dolibarrPickup: {
      setBaseUrl: typeof setBaseUrl
      printProductLabel: typeof printProductLabel
      printPickupLabels: typeof printPickupLabels
      printPickupLineLabels: typeof printPickupLineLabels
      printProductLotLabel: typeof printProductLotLabel
      enhanceStockTransferForm: typeof enhanceStockTransferForm
    }
  }
}

window.dolibarrPickup = {
  setBaseUrl,
  printProductLabel,
  printPickupLabels,
  printPickupLineLabels,
  printProductLotLabel,
  enhanceStockTransferForm
}
