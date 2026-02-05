import React from 'react'
import { Autocomplete, TextField, CircularProgress } from '@mui/material'

/**
 * SearchableSelect - Componente de seleção com busca integrada
 * 
 * @param {string} label - Label do campo
 * @param {any} value - Valor selecionado
 * @param {function} onChange - Callback quando valor muda
 * @param {array} options - Array de opções { value, label }
 * @param {boolean} disabled - Se o campo está desabilitado
 * @param {boolean} loading - Se está carregando opções
 * @param {string} size - Tamanho do campo ('small' | 'medium')
 * @param {string} placeholder - Texto placeholder
 * @param {boolean} freeSolo - Permite valores customizados
 */
function SearchableSelect({ 
  label, 
  value, 
  onChange, 
  options = [], 
  disabled = false,
  loading = false,
  size = 'small',
  placeholder = '-- Selecione --',
  freeSolo = false
}) {
  // Normalizar opções para formato { value, label }
  const normalizedOptions = options.map(opt => {
    if (typeof opt === 'object' && opt !== null) {
      return opt
    }
    return { value: opt, label: String(opt) }
  })

  // Encontrar opção selecionada
  const selectedOption = normalizedOptions.find(opt => {
    // Comparação flexível para números e strings
    return String(opt.value) === String(value)
  }) || null

  const handleChange = (event, newValue) => {
    if (newValue === null) {
      onChange('')
    } else if (typeof newValue === 'string') {
      // freeSolo mode
      onChange(newValue)
    } else {
      onChange(newValue.value)
    }
  }

  return (
    <Autocomplete
      value={selectedOption}
      onChange={handleChange}
      options={normalizedOptions}
      getOptionLabel={(option) => {
        if (typeof option === 'string') return option
        return option.label || String(option.value)
      }}
      isOptionEqualToValue={(option, value) => {
        if (!value) return false
        return String(option.value) === String(value.value)
      }}
      disabled={disabled || loading}
      size={size}
      freeSolo={freeSolo}
      renderInput={(params) => (
        <TextField
          {...params}
          label={label}
          placeholder={placeholder}
          InputProps={{
            ...params.InputProps,
            endAdornment: (
              <>
                {loading ? <CircularProgress color="inherit" size={20} /> : null}
                {params.InputProps.endAdornment}
              </>
            ),
          }}
        />
      )}
      noOptionsText="Nenhuma opção encontrada"
      loadingText="Carregando..."
      clearText="Limpar"
      openText="Abrir"
      closeText="Fechar"
    />
  )
}

export default SearchableSelect
