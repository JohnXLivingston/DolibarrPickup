type Filter = 'upperCase' | 'lowerCase' | 'localeUpperCase' | 'localeLowerCase'

function applyFilter (value: string, filter: Filter): string {
  switch (filter) {
    case 'upperCase':
      return value.toUpperCase()
    case 'lowerCase':
      return value.toLowerCase()
    case 'localeUpperCase':
      return value.toLocaleUpperCase()
    case 'localeLowerCase':
      return value.toLocaleLowerCase()
  }
  return value
}

/**
 * filters data, by removing duplicates. The result is sorted.
 * @param data data returned by the backend source
 * @param column the column on which apply the sort/uniq/filters
 * @param filter filters to apply
 */
function uniqAndSort (data: any[], column: string, filter?: Filter, removeEmpty?: boolean): string[] {
  const seen = new Map<string, true>()
  const r: string[] = []
  for (const line of data) {
    let value: string = line[column] ?? ''
    if (filter) {
      value = applyFilter(value, filter)
    }
    if (removeEmpty && value === '') { continue }
    if (seen.has(value)) { continue }
    seen.set(value, true)

    r.push(value)
  }
  return r.sort((a, b) => a.localeCompare(b))
}

export {
  uniqAndSort,
  Filter
}
