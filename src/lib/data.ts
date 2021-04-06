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

type getDataParams = {[key: string]: string}

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

function getData (dataKey: string, force: boolean = false, params: getDataParams = {}): ResultData {
  if (dataKey.includes(':')) {
    throw new Error('Incorrect key value.')
  }
  let cacheKey = dataKey
  Object.keys(params).sort().forEach(k => {
    cacheKey += ':' + k + '=' + params[k]
  })
  if (cacheKey in cache) {
    if (force) {
      delete cache[cacheKey]
    } else {
      return cache[cacheKey]
    }
  }

  const p = new Promise<void>((resolve, reject) => {
    const url = 'mobile_data.php?action=list&key=' + encodeURIComponent(dataKey)
    const ajax: JQuery.AjaxSettings = {
      dataType: 'json',
      url,
      cache: false
    }
    if (Object.keys(params).length) {
      ajax.method = 'POST'
      ajax.data = params
    }
    $.ajax(ajax).then((data) => {
      if (cache[cacheKey] && cache[cacheKey].status === 'pending' && (cache[cacheKey] as PendingData).promise === p) {
        console.log(`Passing the cache for ${cacheKey} to resolved.`)
        cache[cacheKey] = {
          status: 'resolved',
          data: data,
          promise: p
        }
      } else {
        console.log(`The promise in the cache for ${cacheKey} is not the good one. Discarding.`)
      }
      resolve(data)
    }, (err) => {
      if (cache[cacheKey] && cache[cacheKey].status === 'pending' && (cache[cacheKey] as PendingData).promise === p) {
        console.log(`Passing the cache for ${cacheKey} to rejected.`)
        cache[cacheKey] = {
          status: 'rejected',
          error: err,
          promise: p
        }
      } else {
        console.log(`The promise in the cache for ${cacheKey} is not the good one. Discarding.`)
      }
      if (detectLoginError(err)) {
        console.error('Reloading the page')
        window.location.reload()
      }
      reject(err)
    })
  })

  cache[cacheKey] = {
    status: 'pending',
    promise: p
  }
  return cache[cacheKey]
}

function setData (dataKey: string, data: {[key: string]: string}): Promise<any> {
  const p = new Promise<any>((resolve, reject) => {
    const url = 'mobile_data.php?action=save&key=' + encodeURIComponent(dataKey)
    $.ajax({
      dataType: 'json',
      url,
      cache: false,
      method: 'POST',
      data: data
    }).then((response) => {
      __deleteCache(dataKey)
      resolve(response)
    }, (err) => {
      __deleteCache(dataKey)
      if (detectLoginError(err)) {
        console.error('Reloading the page')
        window.location.reload()
      }
      reject(err)
    })
  })
  return p
}

function __deleteCache (dataKey: string) {
  Object.keys(cache).filter(k => {
    k === dataKey || k.startsWith(dataKey + ':')
  }).forEach(k => delete cache[k])
}

export {
  getData,
  setData,
  getDataParams,
  PendingData,
  ResolvedData,
  RejectedData,
  ResultData
}
