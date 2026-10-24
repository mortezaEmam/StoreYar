import en from '~/locales/en.json'
import fa from '~/locales/fa.json'

type Locale = 'en' | 'fa'
type Dict = Record<string, string>

const dictionaries: Record<Locale, Dict> = { en, fa }

const localeState = ref<Locale>('en')

export function useI18n() {
  const locale = localeState

  function t(key: string): string {
    const dict = dictionaries[locale.value] || dictionaries.en
    return dict[key] ?? dictionaries.en[key] ?? key
  }

  function setLocale(next: Locale) {
    locale.value = next
    if (import.meta.client) {
      localStorage.setItem('storeyar_locale', next)
      document.documentElement.lang = next
      document.documentElement.dir = next === 'fa' ? 'rtl' : 'ltr'
    }
  }

  function hydrateLocale() {
    if (!import.meta.client) return
    const saved = localStorage.getItem('storeyar_locale') as Locale | null
    if (saved === 'fa' || saved === 'en') setLocale(saved)
    else setLocale('en')
  }

  return { locale, t, setLocale, hydrateLocale }
}
