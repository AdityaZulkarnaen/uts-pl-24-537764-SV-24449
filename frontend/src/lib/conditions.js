export const CONDITIONS = [
  { value: 'baik', label: 'Baik' },
  { value: 'rusak_ringan', label: 'Rusak ringan' },
  { value: 'rusak_berat', label: 'Rusak berat' },
]

export function conditionLabel(value) {
  return CONDITIONS.find((condition) => condition.value === value)?.label ?? value
}
