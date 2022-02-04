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

interface UniqAndSortResult {
  values: string[]
  matchingLines: Map<string, any[]>
}

/**
 * filters data, by removing duplicates. The result is sorted.
 * @param data data returned by the backend source
 * @param column the column on which apply the sort/uniq/filters
 * @param filter filters to apply
 */
function uniqAndSort (data: any[], column: string, filter?: Filter, removeEmpty?: boolean): UniqAndSortResult {
  const result: UniqAndSortResult = {
    values: [],
    matchingLines: new Map<string, any>()
  }
  for (const line of data) {
    let value: string = line[column] ?? ''
    if (filter) {
      value = applyFilter(value, filter)
    }
    if (removeEmpty && value === '') { continue }

    if (result.matchingLines.has(value)) {
      result.matchingLines.get(value)?.push(line)
      continue
    }

    result.matchingLines.set(value, [line])
    result.values.push(value)
  }
  result.values.sort((a, b) => a.localeCompare(b))
  return result
}

export {
  uniqAndSort,
  Filter
}
