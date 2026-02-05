import React, { useState } from 'react'
import { 
  Box, 
  Card, 
  CardContent, 
  Typography, 
  Button, 
  TextField, 
  Stack, 
  Alert,
  Divider,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  CircularProgress
} from '@mui/material'
import UploadFileIcon from '@mui/icons-material/UploadFile'
import ArrowBackIcon from '@mui/icons-material/ArrowBack'
import SaveIcon from '@mui/icons-material/Save'

export default function FlowImport({ metadata, csrfToken, onBack }) {
  const [file, setFile] = useState(null)
  const [jsonText, setJsonText] = useState('')
  const [flowName, setFlowName] = useState('')
  const [entityId, setEntityId] = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(false)

  const handleFileChange = (e) => {
    const selected = e.target.files[0]
    if (selected) {
      setFile(selected)
      // Auto-fill flow name from filename if empty
      if (!flowName) {
        setFlowName(selected.name.replace('.json', ''))
      }
      // Read file content into jsonText for visibility/editing? 
      // Or just keep it separate. Let's keep separate but prefer file if present.
      const reader = new FileReader()
      reader.onload = (ev) => {
          setJsonText(ev.target.result)
      }
      reader.readAsText(selected)
    }
  }

  const handleImport = async () => {
    setLoading(true)
    setError(null)
    setSuccess(false)

    try {
      const content = jsonText.trim()
      if (!content) {
        throw new Error("Please upload a file or paste JSON content.")
      }

      // Quick validation
      try {
        JSON.parse(content)
      } catch (e) {
        throw new Error("Invalid JSON format.")
      }

      const payload = {
        name: flowName || 'Imported Flow',
        entities_id: entityId,
        itilcategories_id: categoryId,
        json_content: content
      }

      const res = await fetch('../../marketplace/flow/front/api.php?action=import_flow', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-Glpi-Csrf-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      })

      const data = await res.json()

      if (res.ok && data.status === 'success') {
        setSuccess(true)
        setTimeout(() => {
          onBack() // Go back to list after success
        }, 1500)
      } else {
        throw new Error(data.error || 'Unknown error occurred')
      }

    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <Box>
      <Button 
        startIcon={<ArrowBackIcon />} 
        onClick={onBack} 
        sx={{ mb: 2, color: 'text.secondary' }}
      >
        Voltar para Lista
      </Button>

      <Card>
        <CardContent sx={{ p: 4 }}>
          <Typography variant="h5" sx={{ fontWeight: 800, mb: 1, color: 'white' }}>
            Importar Fluxo
          </Typography>
          <Typography variant="body2" sx={{ color: 'text.secondary', mb: 4 }}>
            Carregue um arquivo JSON ou cole o conteúdo para importar um fluxo legado.
          </Typography>

          <Stack spacing={3} maxWidth="md">
            
            {/* Meta Fields */}
            <Stack direction="row" spacing={2}>
                 <TextField 
                    label="Nome do Fluxo" 
                    fullWidth 
                    value={flowName} 
                    onChange={(e) => setFlowName(e.target.value)}
                    InputLabelProps={{ shrink: true }}
                 />
            </Stack>

            <Stack direction="row" spacing={2}>
                <FormControl fullWidth>
                    <InputLabel id="entity-label" shrink>Entidade</InputLabel>
                    <Select
                        labelId="entity-label"
                        value={entityId}
                        label="Entidade"
                        onChange={(e) => setEntityId(e.target.value)}
                        notched
                        displayEmpty
                    >
                         <MenuItem value="">
                            <em>Selecione...</em>
                        </MenuItem>
                        {metadata?.entities?.map(e => (
                            <MenuItem key={e.id} value={e.id}>{e.completename}</MenuItem>
                        ))}
                    </Select>
                </FormControl>

                <FormControl fullWidth>
                    <InputLabel id="cat-label" shrink>Categoria</InputLabel>
                    <Select
                        labelId="cat-label"
                        value={categoryId}
                        label="Categoria"
                        onChange={(e) => setCategoryId(e.target.value)}
                        notched
                        displayEmpty
                    >
                        <MenuItem value="">
                            <em>Selecione...</em>
                        </MenuItem>
                        {metadata?.categories?.map(c => (
                            <MenuItem key={c.id} value={c.id}>{c.completename}</MenuItem>
                        ))}
                    </Select>
                </FormControl>
            </Stack>

            <Divider sx={{ borderColor: 'rgba(255,255,255,0.1)' }} />

            {/* File Upload */}
            <Box 
              sx={{ 
                border: '2px dashed rgba(255,255,255,0.1)', 
                borderRadius: 2, 
                p: 4, 
                textAlign: 'center',
                cursor: 'pointer',
                '&:hover': { bgcolor: 'rgba(255,255,255,0.02)' }
              }}
              onClick={() => document.getElementById('file-upload').click()}
            >
              <input 
                type="file" 
                id="file-upload" 
                hidden 
                accept=".json" 
                onChange={handleFileChange} 
              />
              <UploadFileIcon sx={{ fontSize: 48, color: 'text.secondary', mb: 2 }} />
              <Typography variant="h6" color="text.primary">
                {file ? file.name : "Clique para selecionar um arquivo .json"}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Ou arraste e solte aqui
              </Typography>
            </Box>

            <Typography variant="body2" sx={{ textAlign: 'center', color: 'text.secondary' }}>OU</Typography>

            {/* JSON Text Area */}
            <TextField
              label="Conteúdo JSON"
              multiline
              rows={8}
              fullWidth
              value={jsonText}
              onChange={(e) => setJsonText(e.target.value)}
              placeholder='[{"stepName": "Start", ...}]'
              InputLabelProps={{ shrink: true }}
              sx={{ fontFamily: 'monospace' }}
            />

            {/* Actions */}
            <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 2, mt: 2 }}>
                <Button 
                    variant="contained" 
                    size="large"
                    onClick={handleImport}
                    disabled={loading || !jsonText}
                    startIcon={loading ? <CircularProgress size={20} color="inherit" /> : <SaveIcon />}
                >
                    {loading ? 'Importando...' : 'Importar Fluxo'}
                </Button>
            </Box>

            {/* Feedback */}
            {error && (
                <Alert severity="error" sx={{ mt: 2 }}>
                    {error}
                </Alert>
            )}
             {success && (
                <Alert severity="success" sx={{ mt: 2 }}>
                    Fluxo importado com sucesso! Redirecionando...
                </Alert>
            )}

          </Stack>
        </CardContent>
      </Card>
    </Box>
  )
}
