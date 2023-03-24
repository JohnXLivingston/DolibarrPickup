import { getUrl } from './utils'

async function generateBatchNumber (): Promise<string | null> {
  const url = getUrl('mobile_data.php', {
    action: 'generate',
    key: 'batchnumber'
  })
  const response = await fetch(url, {
    method: 'GET',
    cache: 'no-cache'
  })
  const contentType = response.headers.get('content-type')
  if (!contentType || !contentType.includes('application/json')) { return null }
  const json = await response.json()
  return json.batch_number
}

function createGenerateBatchNumberButton (input: HTMLInputElement): void {
  const b = document.createElement('a')
  b.classList.add('button')
  b.classList.add('buttongen')
  b.innerHTML = '&larr;Générer'
  b.title = 'Générer un numéro de lot/série'
  b.onclick = async () => {
    input.value = await generateBatchNumber() ?? ''
  }
  input.after(b)
}

export {
  createGenerateBatchNumberButton
}
