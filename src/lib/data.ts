interface ResolvedData {
  status: 'resolved',
  data: any,
  promise: Promise<any>
}

interface PendingData {
  status: 'pending',
  promise: Promise<any>
}

interface RejectedData {
  status: 'rejected',
  error: any,
  promise: Promise<any>
}

type ResultData = ResolvedData | PendingData | RejectedData

const cache: {[key: string]: ResultData} = {}

const doctypeRegex = /^<!doctype html/i
const inputLoginRegex = /<input\s[^>]*name="username"/i
function detectLoginError (err: JQuery.jqXHR | undefined): boolean {
  if (!err || !err.responseText) {
    return false
  }
  if (!doctypeRegex.test(err.responseText)) {
    return false
  }
  if (!inputLoginRegex.test(err.responseText)) {
    return false
  }
  console.error('The user has logout, need to refresh the page...')
  return true
}

function getData (key: string, force: boolean = false): ResultData {
  if (key in cache) {
    if (force) {
      delete cache[key]
    } else {
      return cache[key]
    }
  }

  const p = new Promise<void>((resolve, reject) => {
    const url = 'mobile_data.php?action=list&key=' + encodeURIComponent(key)
    $.ajax({
      dataType: 'json',
      url,
      cache: false
    }).then((data) => {
      if (cache[key] && cache[key].status === 'pending' && (cache[key] as PendingData).promise === p) {
        console.log(`Passing the cache for ${key} to resolved.`)
        cache[key] = {
          status: 'resolved',
          data: data,
          promise: p
        }
      } else {
        console.log(`The promise in the cache for ${key} is not the good one. Discarding.`)
      }
      resolve(data)
    }, (err) => {
      if (cache[key] && cache[key].status === 'pending' && (cache[key] as PendingData).promise === p) {
        console.log(`Passing the cache for ${key} to rejected.`)
        cache[key] = {
          status: 'rejected',
          error: err,
          promise: p
        }
      } else {
        console.log(`The promise in the cache for ${key} is not the good one. Discarding.`)
      }
      if (detectLoginError(err)) {
        console.error('Reloading the page')
        window.location.reload()
      }
      reject(err)
    })
  })

  cache[key] = {
    status: 'pending',
    promise: p
  }
  return cache[key]
}

function setData (key: string, data: {[key: string]: string}): Promise<any> {
  const p = new Promise<any>((resolve, reject) => {
    const url = 'mobile_data.php?action=save&key=' + encodeURIComponent(key)
    $.ajax({
      dataType: 'json',
      url,
      cache: false,
      method: 'POST',
      data: data
    }).then((response) => {
      delete cache[key]
      resolve(response)
    }, (err) => {
      delete cache[key]
      if (detectLoginError(err)) {
        console.error('Reloading the page')
        window.location.reload()
      }
      reject(err)
    })
  })
  return p
}

export {
  getData,
  setData,
  PendingData,
  ResolvedData,
  RejectedData,
  ResultData
}
