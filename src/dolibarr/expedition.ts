const batchNumber2BatchId = new Map<string, number[]>()
const batchId2BatchNumber = new Map<number, string>()
const batchNumber2BatchStatus = new Map<string, '1' | '2'>()

const resetedLines = new Map<number, true>()
const alreadyDoneBatchNumber = new Map<string, true>()

/**
 * If label scan functionnality is enabled,
 * add the possibility to scan labels on the expedition/create form.
 */
function addScanLabelsToExpeditionForm (guessSelector: string): void {
  batchNumber2BatchId.clear()
  batchId2BatchNumber.clear()
  batchNumber2BatchStatus.clear()
  resetedLines.clear()
  alreadyDoneBatchNumber.clear()

  $(function () {
    const table = findTable(guessSelector)
    if (!table || !table.length) {
      return
    }
    const form = createForm()
    const textarea = form.find('textarea')
    textarea.on('keyup', function (e) {
      if (e.key !== 'Enter') {
        return
      }
      refresh(table, $(this))
    })
    table.before(form)
  })
}

interface FillBatchInfosParam {
  [key: string]: {
    productBatchId: number[]
    statusBatch: '1' | '2'
  }
}

function fillBatchInfos (data: FillBatchInfosParam): void {
  for (const batchNumber in data) {
    batchNumber2BatchId.set(batchNumber, data[batchNumber].productBatchId)
    for (const pbi of data[batchNumber].productBatchId) {
      batchId2BatchNumber.set(pbi, batchNumber)
    }
    batchNumber2BatchStatus.set(batchNumber, data[batchNumber].statusBatch)
  }
}

function findTable (guessSelector: string): JQuery | null {
  if (!guessSelector) {
    return null
  }
  return $(guessSelector).closest('table')
}

function createForm (): JQuery {
  const el = $(`
    <label>
      Scannez les étiquettes pour sélectionner les lots.
      <textarea></textarea>
    </label>
  `)
  return el
}

function refresh (table: JQuery, textarea: JQuery): void {
  const val: string = (textarea.val() ?? '').toString()
  const values = val.split(/\n|\r/).map(s => s.trim()).filter(s => s !== '')
  for (const batch of values) {
    if (alreadyDoneBatchNumber.has(batch)) { continue }
    alreadyDoneBatchNumber.set(batch, true)

    const batchIds = batchNumber2BatchId.get(batch)
    if (!batchIds) { continue }

    const line = getLineForProductBatchId(table, batchIds)
    console.log('Scan pour ' + batch, line)
    if (!line) { continue }

    // Si c'est la première fois que je scanne quelque chose pour cette ligne,
    // je commence par tout mettre à 0.
    if (!resetedLines.has(line.indiceAsked)) {
      table.find(
        'input[name=qtyl' + line.indiceAsked.toString() + '], ' + // qtyl4
        'input[name^=qtyl' + line.indiceAsked.toString() + '_]' // qtyl4_5
      ).val(0)
      resetedLines.set(line.indiceAsked, true)
    }

    // Maintenant je modifie la quantité sur la ligne qui va bien.
    // La quantité qu'il faudra mettre:
    // * statusBatch === '1' => qtyasked - qtydelivered
    // * statusBatch === '2' => 1.
    // * Autre: dans le doute, on prend la même formule que pour '1'
    // Si jamais on arrive à une valeur < 1, on met 1 quand même.
    const statusBatch = batchNumber2BatchStatus.get(batch)
    const qty = statusBatch === '2' ? 1 : Math.max(1, line.qtyAsked - line.qtyDelivered)
    line.qtyInput.val(qty)
  }
}

interface Line {
  indiceAsked: number
  qtyAsked: number
  qtyDelivered: number
  qty: Number
  tr: JQuery
  qtyInput: JQuery
}

function getLineForProductBatchId (table: JQuery, batchIds: number[]): Line | null {
  // Note: j'ai plusieurs batchIds. Normalement je ne devrais en avoir qu'un pour l'entrepot courant.
  let tr: JQuery | undefined
  for (const batchId of batchIds) {
    // Note: si jamais le produit apparait plusieurs fois, je ne traite que la première apparation (d'où le .first())
    tr = table.find('input[name^=batchl][value=' + (batchId.toString()) + ']').first().closest('tr')
    if (tr.length !== 0) {
      break
    }
  }
  if (!tr?.length) {
    return null
  }

  const qtyInput = tr.find('input[name^=qtyl]').first()
  const qty = parseInt(qtyInput.val() as string ?? 0)

  // On remonte à la ligne produit associée:
  if (!qtyInput.length) {
    return null
  }

  const fname = qtyInput.attr('name')
  const matches = fname?.match(/^qtyl(\d+)(_\d+)?$/)
  if (!matches) {
    return null
  }

  const indiceAsked = parseInt(matches[1])
  const qtyAsked = parseInt(table.find('#qtyasked' + indiceAsked.toString()).val() as string || '0')
  const qtyDelivered = parseInt(table.find('#qtydelivered' + indiceAsked.toString()).val() as string || '0')

  return {
    indiceAsked,
    qtyAsked,
    qtyDelivered,
    qty,
    tr,
    qtyInput
  }
}

export {
  addScanLabelsToExpeditionForm,
  fillBatchInfos
}
