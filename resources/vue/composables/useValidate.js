import { reactive, computed } from 'vue'

/**
 * Access nested properties in objects/arrays using dot-notation or wildcard '*'.
 */
function data_get(target, key, defaultValue = null) {
    if (key === null || key === undefined) return target

    const keySegments = Array.isArray(key) ? key : String(key).split('.')
    let current = target

    for (let i = 0; i < keySegments.length; i++) {
        const segment = keySegments[i]

        if (current === null || current === undefined) {
            return defaultValue
        }

        if (segment === '*') {
            if (typeof current !== 'object') return defaultValue

            const remainingKeys = keySegments.slice(i + 1)
            const values = Array.isArray(current) ? current : Object.values(current)

            return values.map((item) => data_get(item, remainingKeys, defaultValue))
        }

        if (typeof current === 'object' && segment in current) {
            current = current[segment]
        } else {
            return defaultValue
        }
    }

    return current !== undefined ? current : defaultValue
}

/**
 * Expands wildcard paths (e.g. 'users.*.email') into matching target keys (e.g. 'users.0.email', 'users.1.email')
 */
function expandWildcardKeys(target, ruleKey) {
    const segments = ruleKey.split('.')
    const wildcardIndex = segments.indexOf('*')

    if (wildcardIndex === -1) {
        return [ruleKey]
    }

    const prefixPath = segments.slice(0, wildcardIndex).join('.')
    const suffixPath = segments.slice(wildcardIndex + 1).join('.')
    const parentValue = data_get(target, prefixPath)

    if (!parentValue || typeof parentValue !== 'object') {
        return []
    }

    const keys = Array.isArray(parentValue)
        ? parentValue.map((_, idx) => idx)
        : Object.keys(parentValue)

    const expanded = []
    for (const k of keys) {
        const currentPath = `${prefixPath}.${k}${suffixPath ? '.' + suffixPath : ''}`
        expanded.push(...expandWildcardKeys(target, currentPath))
    }

    return expanded
}

const ISO_DATE_REGEX = /^\d{4}-\d{2}-\d{2}$/
const ISO_DATETIME_REGEX = /^\d{4}-\d{2}-\d{2}(T|\s)\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})?$/

// Rules definition
const defaultRules = {
    required: (val) => {
        if (val === null || val === undefined) return false
        if (typeof val === 'string') return val.trim().length > 0
        if (Array.isArray(val)) return val.length > 0
        return true
    },
    nullable: (val) => {
        return true
    },
    min: (val, param) => {
        if (val === null || val === undefined || val === '') return true
        const num = Number(param)
        if (typeof val === 'number') return val >= num
        return String(val).length >= num
    },
    max: (val, param) => {
        if (val === null || val === undefined || val === '') return true
        const num = Number(param)
        if (typeof val === 'number') return val <= num
        return String(val).length <= num
    },
    email: (val) => {
        if (!val) return true
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(val))
    },
    numeric: (val) => {
        if (!val && val !== 0) return true
        return !isNaN(val) && !isNaN(parseFloat(val))
    },
    integer: (val) => {
        if (!val && val !== 0) return true
        return Number.isInteger(Number(val)) && /^-?\d+$/.test(String(val).trim())
    },
    boolean: (val) => {
        if (val === null || val === undefined || val === '') return true
        return typeof val === 'boolean' || [1, 0, '1', '0', 'true', 'false', true, false].includes(val)
    },
    url: (val) => {
        if (!val) return true
        try {
            new URL(String(val))
            return true
        } catch {
            return false
        }
    },
    date: (val) => {
        if (!val) return true
        if (!ISO_DATE_REGEX.test(String(val))) return false
        const d = new Date(val)
        return d instanceof Date && !isNaN(d.getTime())
    },
    datetime: (val) => {
        if (!val) return true
        if (!ISO_DATETIME_REGEX.test(String(val))) return false
        const d = new Date(val)
        return d instanceof Date && !isNaN(d.getTime())
    },
    same: (val, targetField, data) => {
        return val === data_get(data, targetField)
    },
    gt: (val, targetParam, data) => {
        if (!val && val !== 0) return true
        const fetchedVal = data_get(data, targetParam)
        const comparisonVal = fetchedVal !== null && fetchedVal !== undefined ? Number(fetchedVal) : Number(targetParam)
        return Number(val) > comparisonVal
    },
    gte: (val, targetParam, data) => {
        if (!val && val !== 0) return true
        const fetchedVal = data_get(data, targetParam)
        const comparisonVal = fetchedVal !== null && fetchedVal !== undefined ? Number(fetchedVal) : Number(targetParam)
        return Number(val) >= comparisonVal
    },
    lt: (val, targetParam, data) => {
        if (!val && val !== 0) return true
        const fetchedVal = data_get(data, targetParam)
        const comparisonVal = fetchedVal !== null && fetchedVal !== undefined ? Number(fetchedVal) : Number(targetParam)
        return Number(val) < comparisonVal
    },
    lte: (val, targetParam, data) => {
        if (!val && val !== 0) return true
        const fetchedVal = data_get(data, targetParam)
        const comparisonVal = fetchedVal !== null && fetchedVal !== undefined ? Number(fetchedVal) : Number(targetParam)
        return Number(val) <= comparisonVal
    },
    starts_with: (val, prefix) => {
        if (!val) return true
        return String(val).startsWith(prefix)
    },
    ends_with: (val, suffix) => {
        if (!val) return true
        return String(val).endsWith(suffix)
    },
    pattern: (val, regexStr) => {
        if (!val) return true
        const cleanPattern = regexStr.replace(/^\/|\/$/g, '')
        return new RegExp(cleanPattern).test(String(val))
    },
    between: (val, rangeStr) => {
        if (!val && val !== 0) return true
        const [min, max] = rangeStr.split(',').map(Number)
        const num = Number(val)
        return num >= min && num <= max
    },
    step: (val, stepVal) => {
        if (!val && val !== 0) return true
        const num = Number(val)
        const step = Number(stepVal)
        if (step === 0) return true
        const remainder = Math.abs((num % step))
        return remainder < 0.000001 || Math.abs(remainder - step) < 0.000001
    },
    mime: (val, allowedMimesStr) => {
        if (!val) return true
        const allowed = allowedMimesStr.split(',').map((m) => m.trim().toLowerCase())
        const checkFile = (file) => file instanceof File && allowed.includes(file.type.toLowerCase())

        if (val instanceof FileList || Array.isArray(val)) {
            return Array.from(val).every(checkFile)
        }
        return checkFile(val)
    },
    size: (val, maxKbStr) => {
        if (!val) return true
        const maxBytes = Number(maxKbStr) * 1024
        const checkSize = (file) => file instanceof File && file.size <= maxBytes

        if (val instanceof FileList || Array.isArray(val)) {
            return Array.from(val).every(checkSize)
        }
        return checkSize(val)
    }
}

// Message generators
const defaultMessages = {
    required: (attr) => `The ${attr} field is required.`,
    min: (attr, param) => `The ${attr} field must be at least ${param}.`,
    max: (attr, param) => `The ${attr} field must not be greater than ${param}.`,
    email: (attr) => `The ${attr} field must be a valid email address.`,
    numeric: (attr) => `The ${attr} field must be a number.`,
    integer: (attr) => `The ${attr} field must be an integer.`,
    boolean: (attr) => `The ${attr} field must be true or false.`,
    url: (attr) => `The ${attr} field must be a valid URL.`,
    date: (attr) => `The ${attr} field must be a valid ISO date (YYYY-MM-DD).`,
    datetime: (attr) => `The ${attr} field must be a valid ISO datetime format.`,
    same: (attr, param, attributes) => `The ${attr} field and ${attributes[param] || param} must match.`,
    gt: (attr, param, attributes) => `The ${attr} field must be greater than ${attributes[param] || param}.`,
    gte: (attr, param, attributes) => `The ${attr} field must be greater than or equal to ${attributes[param] || param}.`,
    lt: (attr, param, attributes) => `The ${attr} field must be less than ${attributes[param] || param}.`,
    lte: (attr, param, attributes) => `The ${attr} field must be less than or equal to ${attributes[param] || param}.`,
    starts_with: (attr, param) => `The ${attr} field must start with "${param}".`,
    ends_with: (attr, param) => `The ${attr} field must end with "${param}".`,
    pattern: (attr) => `The ${attr} field format is invalid.`,
    between: (attr, param) => {
        const [min, max] = param.split(',')
        return `The ${attr} field must be between ${min} and ${max}.`
    },
    step: (attr, param) => `The ${attr} field must be a multiple of ${param}.`,
    mime: (attr, param) => `The ${attr} field must be a file of type: ${param}.`,
    size: (attr, param) => `The ${attr} field must not be greater than ${param} KB.`
}

// Convert "user.first_name" to "User First Name"
function formatAttributeName(key) {
    return key
        .split('.')
        .map(part => part.replace(/_/g, ' '))
        .join(' ')
}

export function useValidate() {
    const make = (data, rules = {}, customMessages = {}, attributes = {}) => {
        const errorBag = computed(() => {
            const result = {}

            for (const [ruleKey, ruleList] of Object.entries(rules)) {
                // Expand wildcard rule keys like 'items.*.name' into concrete paths ('items.0.name', 'items.1.name')
                const targetFields = expandWildcardKeys(data, ruleKey)

                for (const field of targetFields) {
                    const val = data_get(data, field)
                    const attributeName = attributes[field] || attributes[ruleKey] || formatAttributeName(field)
                    const fieldRules = Array.isArray(ruleList) ? ruleList : [ruleList]

                    for (const ruleItem of fieldRules) {
                        let ruleName = ''
                        let ruleParam = null
                        let isValid = true
                        let customFnMessage = null

                        if (typeof ruleItem === 'function') {
                            ruleName = 'custom'
                            const customResult = ruleItem(val, data)
                            if (typeof customResult === 'string') {
                                isValid = false
                                customFnMessage = customResult
                            } else {
                                isValid = Boolean(customResult)
                            }
                        } else if (typeof ruleItem === 'string') {
                            const firstColonIdx = ruleItem.indexOf(':')
                            if (firstColonIdx !== -1) {
                                ruleName = ruleItem.slice(0, firstColonIdx)
                                ruleParam = ruleItem.slice(firstColonIdx + 1)
                            } else {
                                ruleName = ruleItem
                            }

                            if (ruleName === 'start_with') ruleName = 'starts_with'
                            if (ruleName === 'end_with') ruleName = 'ends_with'

                            if (defaultRules[ruleName]) {
                                isValid = defaultRules[ruleName](val, ruleParam, data)
                            }
                        }

                        if (!isValid) {
                            if (!result[field]) {
                                result[field] = []
                            }

                            let message = ''
                            if (customFnMessage) {
                                message = customFnMessage
                            } else if (customMessages[field] && customMessages[field][ruleName]) {
                                const msg = customMessages[field][ruleName]
                                message = typeof msg === 'function' ? msg(attributeName, ruleParam) : msg
                            } else if (customMessages[ruleKey] && customMessages[ruleKey][ruleName]) {
                                const msg = customMessages[ruleKey][ruleName]
                                message = typeof msg === 'function' ? msg(attributeName, ruleParam) : msg
                            } else if (defaultMessages[ruleName]) {
                                message = defaultMessages[ruleName](attributeName, ruleParam, attributes)
                            } else {
                                message = `The ${attributeName} field is invalid.`
                            }

                            result[field].push(message)
                        }
                    }
                }
            }

            return result
        })

        return reactive({
            failed() {
                return Object.keys(errorBag.value).length > 0
            },

            passed() {
                return Object.keys(errorBag.value).length === 0
            },

            has(field) {
              return !!errorBag.value[field];
            },


            error(field) {
                return errorBag.value[field] ? errorBag.value[field][0] : null
            },

            first(field) {
                return this.error(field);
            },

            errors(field = null) {
                if (field) {
                    return errorBag.value[field] || []
                }
                return errorBag.value
            },

            get message() {
                const firstField = Object.keys(errorBag.value)[0]
                return firstField ? errorBag.value[firstField][0] : null
            }
        })
    }

    return { make }
}