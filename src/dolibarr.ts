import { printPickupLabels, printPickupLineLabels, printProductLabel } from './shared/printlabel'

declare global {
  interface Window {
    dolibarrPickupPrintProductLabel: typeof printProductLabel
    dolibarrPickupPrintPickupLabels: typeof printPickupLabels
    dolibarrPickupPrintPickupLineLabels: typeof printPickupLineLabels
  }
}
window.dolibarrPickupPrintProductLabel = printProductLabel
window.dolibarrPickupPrintPickupLabels = printPickupLabels
window.dolibarrPickupPrintPickupLineLabels = printPickupLineLabels
