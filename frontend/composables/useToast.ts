type ToastKind = 'ok' | 'err' | 'hint'

export type ToastItem = {
  id: number
  kind: ToastKind
  message: string
}

const toasts = ref<ToastItem[]>([])
let seq = 0

export function useToast() {
  function push(kind: ToastKind, message: string, ms = 3500) {
    const id = ++seq
    toasts.value = [...toasts.value, { id, kind, message }]
    if (import.meta.client) {
      window.setTimeout(() => {
        toasts.value = toasts.value.filter(t => t.id !== id)
      }, ms)
    }
  }

  return {
    toasts,
    ok: (message: string) => push('ok', message),
    err: (message: string) => push('err', message),
    hint: (message: string) => push('hint', message),
    /**
     * Compatibility: toast.push(message, 'ok' | 'err' | 'hint')
     * Also accepts legacy order: toast.push('ok', message)
     */
    push: (a: string, b?: string) => {
      const kinds: ToastKind[] = ['ok', 'err', 'hint']
      if (b !== undefined && kinds.includes(b as ToastKind)) {
        push(b as ToastKind, a)
        return
      }
      if (kinds.includes(a as ToastKind) && typeof b === 'string') {
        push(a as ToastKind, b)
        return
      }
      push('ok', a)
    },
    dismiss: (id: number) => {
      toasts.value = toasts.value.filter(t => t.id !== id)
    },
  }
}
