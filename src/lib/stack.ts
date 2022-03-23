interface StackValue {
  label: string
  name: string
  value: string
  display?: string // what to display. If undefined, will be the value.
  // object?: string, // the name of the target object for this field
  invalid?: string // if set, indicate the value is invalid
  silent?: boolean // if true, the value will not be sent to backend
  invisible?: boolean // if true, the value will not be shown by displayStackValue
}

interface RemoveBetween {
  from?: string
  to: string
}

class Stack {
  readonly stateName: string
  previous?: Stack
  private values: StackValue[] = []

  constructor (stateName: string, previous?: Stack) {
    this.stateName = stateName
    this.previous = previous
  }

  findByStateName (stateName: string): Stack | undefined {
    if (this.stateName === stateName) return this
    if (!this.previous) {
      return undefined
    }
    return this.previous.findByStateName(stateName)
  }

  // findPreviousByStateName (stateName: string): Stack | undefined {
  //   if (!this.previous) return undefined
  //   if (this.previous.stateName === stateName) return this.previous
  //   return this.previous.findPreviousByStateName(stateName)
  // }

  // cutPrevious (stateName?: string) {
  //   if (!stateName) {
  //     this.previous = undefined
  //     return
  //   }
  //   this.previous = this.findPreviousByStateName(stateName)?.previous
  // }

  getStackValue (fieldName: string): StackValue | undefined {
    const vl = this.values.filter(v => v.name === fieldName)
    if (vl.length > 1) {
      throw new Error('There are multiple values for ' + fieldName + ', dont know how to handle.')
    }
    if (!vl.length) {
      return undefined
    }
    return vl[0]
  }

  getValue (fieldName: string): string | undefined {
    const sv = this.getStackValue(fieldName)
    return sv?.value
  }

  isValueInvalid (fieldName: string): string | undefined {
    const sv = this.getStackValue(fieldName)
    return sv?.invalid
  }

  isAnyValue (): boolean {
    return this.values.length !== 0
  }

  setValues (values: null | StackValue[] | StackValue): void {
    if (values === null) values = []
    if (!Array.isArray(values)) values = [values]
    this.values = values
  }

  changeOrAppendValues (values: StackValue[] | StackValue): void {
    if (!Array.isArray(values)) values = [values]
    for (let i = 0; i < values.length; i++) {
      const value = values[i]
      let j = 0
      for (j = 0; j < this.values.length; j++) {
        if (this.values[j].name !== value.name) continue
        break
      }
      this.values[j] = value
    }
  }

  removeValue (fieldName: string): void {
    this.values = this.values.filter(sv => sv.name !== fieldName)
  }

  static getDisplayStackValue (sv: StackValue): { label: string, display: string } | undefined {
    if (sv.invisible) return undefined
    const display: string = sv.display ?? sv.value
    return {
      label: sv.label,
      display: display
    }
  }

  static displayStackValue (sv: StackValue): string | undefined {
    const r = Stack.getDisplayStackValue(sv)
    if (r === undefined) {
      return undefined
    }
    return `${r.label}: ${r.display}`
  }

  static stackValuesToParams (sva: StackValue[]): {[key: string]: string} {
    const data: {[key: string]: string} = {}
    for (let i = 0; i < sva.length; i++) {
      const sv = sva[i]
      if (sv.silent) continue
      if (sv.invalid) throw new Error('There is an invalid value in the stack. Should not be saved.')
      if (sv.name in data) throw new Error(`Multiple "${sv.name}" parameters.`)
      data[sv.name] = sv.value
    }
    return data
  }

  displayStackValues (separator: string = ' / '): string {
    const a: string[] = []
    for (let i = 0; i < this.values.length; i++) {
      const sv = this.values[i]
      const s = Stack.displayStackValue(sv)
      if (s === undefined) continue
      a.push(s)
    }
    return a.join(separator)
  }

  searchValue (fieldName: string): string | undefined {
    const v = this.getValue(fieldName)
    if (v) return v
    if (this.previous) return this.previous.searchValue(fieldName)
    return undefined
  }

  getAllStackValues (): StackValue[] {
    let sva: StackValue[]
    if (this.previous) {
      sva = this.previous.getAllStackValues()
    } else {
      sva = []
    }
    for (let i = 0; i < this.values.length; i++) {
      sva.push(this.values[i])
    }
    return sva
  }

  getStackValuesUntil (stateName: string): StackValue[] | undefined {
    let sva: StackValue[] | undefined
    if (this.stateName === stateName) {
      sva = []
    } else {
      if (this.previous) {
        sva = this.previous.getStackValuesUntil(stateName)
        if (sva === undefined) return undefined
      } else {
        console.error('Did not find state ' + stateName + ' when calling getStackValuesUntil')
        return undefined
      }
    }
    for (let i = 0; i < this.values.length; i++) {
      sva.push(this.values[i])
    }
    return sva
  }

  private dump (): any {
    const vd = []
    for (let i = 0; i < this.values.length; i++) {
      vd.push(Object.assign({}, this.values[i]))
    }
    const d = Object.assign({}, {
      stateName: this.stateName,
      values: vd,
      previous: this.previous ? this.previous.dump() : null
    })
    return d
  }

  serialize (version: string): string {
    return JSON.stringify({
      version,
      dump: this.dump()
    })
  }

  static deserialize (s: string, version: string): Stack | null {
    const o = JSON.parse(s)
    if (typeof o !== 'object') {
      return null
    }
    if (o.version !== version) {
      return null
    }
    if (!o.dump) {
      return null
    }
    function createObject (d: any): Stack {
      if (typeof d !== 'object') {
        throw new Error('Unable to deserialize')
      }
      if (!d.stateName) {
        throw new Error('Missing stateName')
      }
      const previous = d.previous ? createObject(d.previous) : undefined
      const s = new Stack(d.stateName, previous)
      s.setValues(d.values)
      return s
    }
    return createObject(o.dump)
  }

  dumpForHuman (): string[] {
    const a = [this.stateName + ': ' + JSON.stringify(this.values)]
    if (!this.previous) {
      return a
    }
    return a.concat(this.previous.dumpForHuman())
  }
}

export {
  Stack,
  StackValue,
  RemoveBetween
}
