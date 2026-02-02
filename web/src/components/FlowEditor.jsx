import React, { useState, useEffect } from 'react'
import { 
  Box, Typography, Button, TextField, Select, MenuItem, FormControl, InputLabel, 
  Grid, Card, CardContent, Accordion, AccordionSummary, AccordionDetails, 
  IconButton, Chip, Drawer, Divider, Stack, List, ListItem, ListItemText, ListItemSecondaryAction, 
  Paper, Tooltip
} from '@mui/material'
import ArrowBackIcon from '@mui/icons-material/ArrowBack'
import SaveIcon from '@mui/icons-material/Save'
import ExpandMoreIcon from '@mui/icons-material/ExpandMore'
import AddIcon from '@mui/icons-material/Add'
import DeleteIcon from '@mui/icons-material/Delete'
import SettingsIcon from '@mui/icons-material/Settings'
import FlashOnIcon from '@mui/icons-material/FlashOn'
import VerifiedUserIcon from '@mui/icons-material/VerifiedUser'
import CloseIcon from '@mui/icons-material/Close'
import AccountTreeIcon from '@mui/icons-material/AccountTree'
import SearchableSelect from './SearchableSelect'

function FlowEditor({ id, metadata, csrfToken, onBack }) {
  const [flow, setFlow] = useState({
    name: '',
    description: '',
    entities_id: 0,
    itilcategories_id: 0,
    is_active: 1,
    steps: []
  })
  const [loading, setLoading] = useState(id !== null)
  const [editingItem, setEditingItem] = useState(null) // { stepIdx, type, itemIdx, data }
  const [rawJson, setRawJson] = useState('')
  const [jsonError, setJsonError] = useState(null)
  const [dbTables, setDbTables] = useState([])
  const [dbFields, setDbFields] = useState({}) // { tableName: [fields] }
  const [taskTemplatePreview, setTaskTemplatePreview] = useState(null)
  const [tags, setTags] = useState([])

  useEffect(() => {
    fetch('../marketplace/flow/front/api.php?action=get_tables')
      .then(res => res.json())
      .then(setDbTables)
  }, [])

  useEffect(() => {
    fetch('../marketplace/flow/front/api.php?action=get_tags')
      .then(res => res.json())
      .then(setTags)
      .catch(() => setTags([])) // Fallback if tag plugin not installed
  }, [])

  useEffect(() => {
    if (editingItem) {
      setRawJson(JSON.stringify(editingItem.data.config || {}, null, 2))
      setJsonError(null)
      
      const currentTable = editingItem.data.config?.table;
      const baseForConsultation = editingItem.data.config?.base_for_consultation;
      
      if (baseForConsultation === 'form') {
        const ticketTable = 'glpi_tickets';
        if (!dbFields[ticketTable]) fetchFields(ticketTable);
      } else if (currentTable && !dbFields[currentTable]) {
        fetchFields(currentTable);
      }
    }
  }, [editingItem])

  const fetchFields = (tableName) => {
    if (!tableName || dbFields[tableName]) return;
    fetch(`../marketplace/flow/front/api.php?action=get_fields&table=${tableName}`)
      .then(res => res.json())
      .then(fields => {
        setDbFields(prev => ({ ...prev, [tableName]: fields }));
      });
  }

  const fetchTaskTemplatePreview = (templateId) => {
    if (!templateId || templateId <= 0) {
      setTaskTemplatePreview(null);
      return;
    }
    fetch(`../marketplace/flow/front/api.php?action=get_task_template_preview&id=${templateId}`)
      .then(res => res.json())
      .then(preview => {
        if (!preview.error) {
          setTaskTemplatePreview(preview);
        }
      });
  }

  useEffect(() => {
    if (id) {
      fetch(`../marketplace/flow/front/api.php?action=get_flow&id=${id}`)
        .then(res => res.json())
        .then(data => {
          setFlow(data)
          setLoading(false)
        })
    }
  }, [id])

  const handleSave = () => {
    fetch('../marketplace/flow/front/api.php?action=save_flow', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-Glpi-Csrf-Token': csrfToken
      },
      body: JSON.stringify(flow)
    }).then(() => onBack())
  }

  const addStep = () => {
    const newStep = {
      id: null,
      name: 'Novo Passo',
      step_type: 'Common',
      actions: [],
      validations: []
    }
    setFlow({ ...flow, steps: [...(flow.steps || []), newStep] })
  }

  const removeStep = (index) => {
    const newSteps = [...flow.steps]
    newSteps.splice(index, 1)
    setFlow({ ...flow, steps: newSteps })
  }

  const updateStep = (index, data) => {
    const newSteps = [...flow.steps]
    newSteps[index] = { ...newSteps[index], ...data }
    setFlow({ ...flow, steps: newSteps })
  }

  const addItem = (stepIndex, listKey, itemData) => {
    const newSteps = [...flow.steps]
    newSteps[stepIndex][listKey] = [...(newSteps[stepIndex][listKey] || []), itemData]
    setFlow({ ...flow, steps: newSteps })
  }

  const removeItem = (stepIndex, listKey, itemIndex) => {
    const newSteps = [...flow.steps]
    newSteps[stepIndex][listKey].splice(itemIndex, 1)
    setFlow({ ...flow, steps: newSteps })
  }

  const updateItemConfig = (key, value) => {
    const { stepIdx, type, itemIdx } = editingItem
    const newSteps = [...flow.steps]
    const item = { ...newSteps[stepIdx][type][itemIdx] }
    item.config = { ...item.config, [key]: value }
    if (key === 'table' || (key === 'base_for_consultation' && value === 'form')) {
      const targetTable = key === 'table' ? value : 'glpi_tickets';
      fetchFields(targetTable);
      if (key === 'base_for_consultation' && value === 'form') {
        item.config.table = 'glpi_tickets';
      }
    }
    newSteps[stepIdx][type][itemIdx] = item
    setFlow({ ...flow, steps: newSteps })
    setEditingItem({ ...editingItem, data: item })
    setRawJson(JSON.stringify(item.config, null, 2))
  }

  const handleRawJsonChange = (val) => {
    setRawJson(val)
    try {
      const parsed = JSON.parse(val)
      setJsonError(null)
      const { stepIdx, type, itemIdx } = editingItem
      const newSteps = [...flow.steps]
      const item = { ...newSteps[stepIdx][type][itemIdx] }
      item.config = parsed
      newSteps[stepIdx][type][itemIdx] = item
      setFlow({ ...flow, steps: newSteps })
      // Don't update editingItem.data here to avoid cursor jumping, 
      // but the flow state is updated.
    } catch (e) {
      setJsonError("JSON Inválido: " + e.message)
    }
  }

  if (loading) return <Box sx={{ textAlign: 'center', py: 10, color: 'text.secondary' }}>Carregando detalhes...</Box>

  return (
    <Box sx={{ animation: 'fadeIn 0.5s ease-out' }}>
      <Paper sx={{ mb: 4, p: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center', bgcolor: 'background.paper' }}>
        <Button startIcon={<ArrowBackIcon />} onClick={onBack} color="inherit">Voltar</Button>
        <Button variant="contained" startIcon={<SaveIcon />} onClick={handleSave} size="large">Salvar Fluxo</Button>
      </Paper>

      <Grid container spacing={4}>
        <Grid item xs={12} lg={4}>
          <Card>
            <CardContent>
              <Typography variant="h6" sx={{ fontWeight: 700, mb: 3 }}>Informações Básicas</Typography>
              <Stack spacing={3}>
                <TextField 
                  label="Nome do Fluxo" 
                  fullWidth 
                  value={flow.name}
                  onChange={e => setFlow({...flow, name: e.target.value})}
                  placeholder="Ex: Compras e Suprimentos" 
                />
                <TextField 
                  label="Descrição" 
                  fullWidth 
                  multiline 
                  rows={4}
                  value={flow.description}
                  onChange={e => setFlow({...flow, description: e.target.value})}
                />
                
                <FormControl fullWidth>
                  <InputLabel>Entidade</InputLabel>
                  <Select 
                    label="Entidade"
                    value={flow.entities_id}
                    onChange={e => setFlow({...flow, entities_id: e.target.value})}
                  >
                    <MenuItem value="0">Root Entity</MenuItem>
                    {metadata?.entities?.map(e => <MenuItem key={e.id} value={e.id}>{e.completename || e.name}</MenuItem>)}
                  </Select>
                </FormControl>

                <FormControl fullWidth>
                  <InputLabel>Categoria</InputLabel>
                  <Select 
                    label="Categoria"
                    value={flow.itilcategories_id}
                    onChange={e => setFlow({...flow, itilcategories_id: e.target.value})}
                  >
                    <MenuItem value="0">-- Selecione --</MenuItem>
                    {metadata?.categories?.map(c => <MenuItem key={c.id} value={c.id}>{c.completename || c.name}</MenuItem>)}
                  </Select>
                </FormControl>
              </Stack>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} lg={8}>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 3, alignItems: 'center' }}>
            <Typography variant="h5" sx={{ fontWeight: 700, display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <AccountTreeIcon sx={{ color: 'primary.main' }} /> Passos do Fluxo
            </Typography>
            <Button variant="outlined" startIcon={<AddIcon />} onClick={addStep}>Adicionar Passo</Button>
          </Box>

          <Box sx={{ pb: 4 }}>
            {flow.steps?.map((step, sIdx) => (
              <Accordion key={step.id || `new-${sIdx}`} sx={{ mb: 2, '&:before': { display: 'none' } }}>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, width: '100%' }}>
                    <Typography variant="caption" sx={{ fontFamily: 'monospace', opacity: 0.5 }}>#{step.id || 'NEW'}</Typography>
                    <Typography sx={{ fontWeight: 'bold', flexGrow: 1 }}>{step.name}</Typography>
                    <Chip label={step.step_type} size="small" variant="outlined" sx={{ mr: 2 }} />
                  </Box>
                </AccordionSummary>
                <AccordionDetails sx={{ borderTop: '1px solid rgba(255,255,255,0.05)', pt: 3, pb: 4, px: 3, bgcolor: 'rgba(255,255,255,0.01)', overflow: 'visible' }}>
                  <Stack spacing={3}>
                    <Grid container spacing={2}>
                      <Grid item xs={12} sm={6}>
                        <TextField 
                          label="Nome do Passo" 
                          fullWidth 
                          size="small"
                          value={step.name}
                          onChange={e => updateStep(sIdx, { name: e.target.value })}
                          sx={{ mb: 3 }}
                        />
                      </Grid>
                      <Grid item xs={12} sm={6}>
                        <FormControl fullWidth size="small">
                          <InputLabel>Tipo de Passo</InputLabel>
                          <Select 
                            label="Tipo de Passo"
                            value={step.step_type}
                            onChange={e => updateStep(sIdx, { step_type: e.target.value })}
                          >
                            <MenuItem value="Initial">Inicial</MenuItem>
                            <MenuItem value="Common">Comum</MenuItem>
                            <MenuItem value="Condition">Condição</MenuItem>
                            <MenuItem value="End">Fim</MenuItem>
                          </Select>
                        </FormControl>
                      </Grid>
                    </Grid>

                    <Grid container spacing={2}>
                      {/* Actions */}
                      <Grid item xs={12} md={6}>
                        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1.5 }}>
                          <Typography variant="overline" sx={{ display: 'flex', alignItems: 'center', gap: 1, fontWeight: 'bold' }}>
                            <FlashOnIcon sx={{ fontSize: 16, color: 'warning.main' }} /> Ações
                          </Typography>
                          <Select 
                            size="small" 
                            displayEmpty 
                            value=""
                            onChange={e => { 
                              if (e.target.value) {
                                addItem(sIdx, 'actions', { action_type: e.target.value, config: {} }); 
                              }
                            }}
                            sx={{ height: 30, fontSize: '0.75rem' }}
                          >
                            <MenuItem value="" disabled>+ Adicionar</MenuItem>
                            {metadata?.action_types?.map(at => <MenuItem key={at.action_type} value={at.action_type}>{at.name || at.action_type}</MenuItem>)}
                          </Select>
                        </Box>
                        <List dense sx={{ bgcolor: 'rgba(255,255,255,0.02)', borderRadius: 2 }}>
                          {step.actions?.map((action, aIdx) => (
                            <ListItem key={aIdx} sx={{ borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
                              <ListItemText 
                                primary={action.action_type}
                                secondary={
                                  <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5, mt: 0.5 }}>
                                    {Object.entries(action.config || {}).map(([k,v]) => (
                                      <Chip key={k} label={`${k}: ${v}`} size="small" sx={{ height: 16, fontSize: '9px', bgcolor: 'rgba(99, 102, 241, 0.1)', color: 'primary.light' }} />
                                    ))}
                                    {Object.keys(action.config || {}).length === 0 && "Sem configuração"}
                                  </Box>
                                }
                                primaryTypographyProps={{ variant: 'body2', fontWeight: 600 }}
                                secondaryTypographyProps={{ component: 'div' }}
                              />
                              <ListItemSecondaryAction>
                                <IconButton size="small" onClick={() => setEditingItem({ stepIdx: sIdx, type: 'actions', itemIdx: aIdx, data: action })}>
                                  <SettingsIcon fontSize="inherit" />
                                </IconButton>
                                <IconButton size="small" color="error" onClick={() => removeItem(sIdx, 'actions', aIdx)}>
                                  <DeleteIcon fontSize="inherit" />
                                </IconButton>
                              </ListItemSecondaryAction>
                            </ListItem>
                          ))}
                        </List>
                      </Grid>

                      {/* Validations */}
                      <Grid item xs={12} md={6}>
                        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1.5 }}>
                          <Typography variant="overline" sx={{ display: 'flex', alignItems: 'center', gap: 1, fontWeight: 'bold' }}>
                            <VerifiedUserIcon sx={{ fontSize: 16, color: 'success.main' }} /> Validações
                          </Typography>
                          <Select 
                            size="small" 
                            displayEmpty 
                            value=""
                            onChange={e => { 
                              if (e.target.value) {
                                addItem(sIdx, 'validations', { validation_type: e.target.value, severity: 'BLOCKER', config: {} }); 
                              }
                            }}
                            sx={{ height: 30, fontSize: '0.75rem' }}
                          >
                            <MenuItem value="" disabled>+ Adicionar</MenuItem>
                            {metadata?.validation_types?.map(vt => <MenuItem key={vt.validation_type} value={vt.validation_type}>{vt.name || vt.validation_type}</MenuItem>)}
                          </Select>
                        </Box>
                        <List dense sx={{ bgcolor: 'rgba(255,255,255,0.02)', borderRadius: 2 }}>
                          {step.validations?.map((val, vIdx) => (
                            <ListItem key={vIdx} sx={{ borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
                              <ListItemText 
                                primary={val.validation_type}
                                secondary={
                                  <Box>
                                    <Typography variant="caption" sx={{ color: val.severity === 'BLOCKER' ? 'error.main' : 'warning.main', fontWeight: 'bold', display: 'block', mb: 0.5 }}>
                                      {val.severity}
                                    </Typography>
                                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                      {Object.entries(val.config || {}).map(([k,v]) => (
                                        <Chip key={k} label={`${k}: ${v}`} size="small" sx={{ height: 16, fontSize: '9px', bgcolor: 'rgba(76, 175, 80, 0.1)', color: 'success.light' }} />
                                      ))}
                                    </Box>
                                  </Box>
                                }
                                primaryTypographyProps={{ variant: 'body2', fontWeight: 600 }}
                                secondaryTypographyProps={{ component: 'div' }}
                              />
                              <ListItemSecondaryAction>
                                <IconButton size="small" onClick={() => setEditingItem({ stepIdx: sIdx, type: 'validations', itemIdx: vIdx, data: val })}>
                                  <SettingsIcon fontSize="inherit" />
                                </IconButton>
                                <IconButton size="small" color="error" onClick={() => removeItem(sIdx, 'validations', vIdx)}>
                                  <DeleteIcon fontSize="inherit" />
                                </IconButton>
                              </ListItemSecondaryAction>
                            </ListItem>
                          ))}
                        </List>
                      </Grid>
                    </Grid>

                    <Divider />
                    <Button 
                      variant="text" 
                      color="error" 
                      startIcon={<DeleteIcon />} 
                      onClick={() => removeStep(sIdx)}
                      sx={{ alignSelf: 'flex-start' }}
                    >
                      Remover este passo
                    </Button>
                  </Stack>
                </AccordionDetails>
              </Accordion>
            ))}
          </Box>
        </Grid>
      </Grid>

      <Drawer 
        anchor="right" 
        open={!!editingItem} 
        onClose={() => setEditingItem(null)}
        PaperProps={{ sx: { width: { xs: '100%', sm: 450 }, p: 4, bgcolor: 'background.default' } }}
      >
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 4 }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 800 }}>Configuração</Typography>
            <Typography variant="caption" color="primary" sx={{ textTransform: 'uppercase', fontWeight: 'bold' }}>
              {editingItem?.data?.[editingItem?.type === 'actions' ? 'action_type' : 'validation_type']}
            </Typography>
          </Box>
          <IconButton onClick={() => setEditingItem(null)}><CloseIcon /></IconButton>
        </Box>

        <Stack spacing={4}>
          {editingItem?.type === 'validations' && (
            <FormControl fullWidth>
              <InputLabel>Severidade</InputLabel>
              <Select 
                label="Severidade"
                value={editingItem.data.severity}
                onChange={e => {
                  const newSteps = [...flow.steps]
                  newSteps[editingItem.stepIdx].validations[editingItem.itemIdx].severity = e.target.value
                  setFlow({ ...flow, steps: newSteps })
                  setEditingItem({ ...editingItem, data: { ...editingItem.data, severity: e.target.value } })
                }}
              >
                <MenuItem value="BLOCKER">BLOCKER (Impedir Gravação)</MenuItem>
                <MenuItem value="WARNING">WARNING (Apenas Aviso)</MenuItem>
              </Select>
            </FormControl>
          )}

          <Box>
            <Typography variant="overline" color="text.secondary" sx={{ fontWeight: 'bold', mb: 2, display: 'block' }}>
              Parâmetros de Configuração
            </Typography>
            <Stack spacing={3}>
              {(() => {
                if (!editingItem) return null;
                const typeKey = editingItem.type === 'actions' ? 'action_type' : 'validation_type';
                const typeName = editingItem.data[typeKey];
                const typeMeta = (editingItem.type === 'actions' ? metadata?.action_types : metadata?.validation_types)
                  ?.find(t => t[typeKey] === typeName);
                
                const schema = typeMeta?.config_schema?.properties || {};
                
                if (Object.keys(schema).length === 0) {
                  return <Typography variant="body2" color="text.secondary" sx={{ fontStyle: 'italic' }}>Este item não requer configuração adicional.</Typography>;
                }

                return Object.entries(schema).map(([key, field]) => {
                  const baseConsultation = editingItem.data.config?.base_for_consultation;
                  const isTableField = key === 'table';
                  const isColumnField = key === 'field' || key === 'fieldIndex';
                  const isFieldIndex = key === 'fieldIndex';
                  const currentTable = baseConsultation === 'form' ? 'glpi_tickets' : editingItem.data.config?.table;

                  // Smart detection for special fields
                  const isUserField = key === 'users_id' || key === 'user_id';
                  const isGroupField = key === 'groups_id' || key === 'group_id';
                  const isTaskTemplateField = key === 'tasktemplates_id';
                  const isTagField = key.toLowerCase().includes('tag') && !isTableField;

                  // Hide table and fieldIndex if consulting form
                  if ((isTableField || isFieldIndex) && baseConsultation === 'form') return null;

                  return (
                    <Box key={key}>
                      {field.enum ? (
                        <FormControl fullWidth size="small">
                          <InputLabel>{key}</InputLabel>
                          <Select
                            label={key}
                            value={editingItem.data.config?.[key] || field.default || ''}
                            onChange={e => updateItemConfig(key, e.target.value)}
                          >
                            <MenuItem value="">-- Selecione --</MenuItem>
                            {field.enum.map(opt => (
                              <MenuItem key={opt} value={opt}>{opt}</MenuItem>
                            ))}
                          </Select>
                        </FormControl>
                      ) : isUserField ? (
                        <SearchableSelect
                          label="Usuário"
                          value={editingItem.data.config?.[key] || ''}
                          onChange={val => updateItemConfig(key, parseInt(val) || 0)}
                          options={(metadata?.users || []).map(u => ({ value: u.id, label: u.completename }))}
                          size="small"
                          placeholder="-- Selecione o Usuário --"
                        />
                      ) : isGroupField ? (
                        <SearchableSelect
                          label="Grupo"
                          value={editingItem.data.config?.[key] || ''}
                          onChange={val => updateItemConfig(key, parseInt(val) || 0)}
                          options={(metadata?.groups || []).map(g => ({ value: g.id, label: g.completename }))}
                          size="small"
                          placeholder="-- Selecione o Grupo --"
                        />
                      ) : isTaskTemplateField ? (
                        <>
                          <SearchableSelect
                            label="Template de Tarefa"
                            value={editingItem.data.config?.[key] || ''}
                            onChange={val => {
                              const templateId = parseInt(val) || 0;
                              updateItemConfig(key, templateId);
                              fetchTaskTemplatePreview(templateId);
                            }}
                            options={(metadata?.task_templates || []).map(t => ({ value: t.id, label: t.name }))}
                            size="small"
                            placeholder="-- Selecione o Template --"
                          />
                          {taskTemplatePreview && (
                            <Paper elevation={2} sx={{ p: 2, mt: 2, bgcolor: 'background.default' }}>
                              <Typography variant="caption" color="primary" sx={{ fontWeight: 'bold', display: 'block', mb: 1 }}>
                                PREVIEW DO TEMPLATE
                              </Typography>
                              <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap', fontFamily: 'monospace', fontSize: '11px' }}>
                                {taskTemplatePreview.content || '(sem conteúdo)'}
                              </Typography>
                            </Paper>
                          )}
                        </>
                      ) : isTagField ? (
                        <SearchableSelect
                          label="Tag"
                          value={editingItem.data.config?.[key] || ''}
                          onChange={val => updateItemConfig(key, parseInt(val) || 0)}
                          options={tags.map(tag => ({ value: tag.id, label: tag.name }))}
                          size="small"
                          placeholder="-- Selecione a Tag --"
                        />
                      ) : isTableField ? (
                        <SearchableSelect
                          label="Tabela (GLPI)"
                          value={editingItem.data.config?.[key] || ''}
                          onChange={val => updateItemConfig(key, val)}
                          options={dbTables.map(t => ({ value: t, label: t }))}
                          size="small"
                          placeholder="-- Selecione a Tabela --"
                        />
                      ) : (isColumnField && currentTable) ? (
                        <Box>
                          <SearchableSelect
                            label={key === 'field' ? 'Campo' : 'Campo de Índice'}
                            value={editingItem.data.config?.[key] || ''}
                            onChange={val => updateItemConfig(key, val)}
                            options={(dbFields[currentTable] || []).map(f => ({ value: f, label: f }))}
                            disabled={!dbFields[currentTable]}
                            loading={!dbFields[currentTable]}
                            size="small"
                            placeholder="-- Selecione o Campo --"
                          />
                          {!dbFields[currentTable] && <Typography variant="caption" color="primary">Carregando campos...</Typography>}
                        </Box>
                      ) : (
                        <TextField
                          fullWidth
                          size="small"
                          label={key}
                          type={field.type === 'integer' ? 'number' : 'text'}
                          value={editingItem.data.config?.[key] ?? ''}
                          onChange={e => updateItemConfig(key, field.type === 'integer' ? parseInt(e.target.value) || 0 : e.target.value)}
                          helperText={field.description || `Tipo: ${field.type}`}
                        />
                      )}
                    </Box>
                  );
                });
              })()}
            </Stack>
          </Box>

          <Accordion sx={{ bgcolor: 'transparent', boxShadow: 'none', '&:before': { display: 'none' } }}>
            <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={{ p: 0, minHeight: 0, '& .MuiAccordionSummary-content': { my: 1 } }}>
              <Typography variant="caption" color="primary" sx={{ fontWeight: 'bold' }}>EDITAR JSON BRUTO (AVANÇADO)</Typography>
            </AccordionSummary>
            <AccordionDetails sx={{ p: 0 }}>
              <TextField
                fullWidth
                multiline
                rows={6}
                variant="filled"
                value={rawJson}
                onChange={e => handleRawJsonChange(e.target.value)}
                error={!!jsonError}
                helperText={jsonError || "Edite o JSON bruto desta configuração."}
                sx={{ 
                  '& .MuiInputBase-input': { fontFamily: 'monospace', fontSize: '11px' },
                  bgcolor: 'rgba(0,0,0,0.3)',
                  borderRadius: 1
                }}
              />
            </AccordionDetails>
          </Accordion>
        </Stack>

        <Box sx={{ mt: 'auto', pt: 4 }}>
          <Button fullWidth variant="contained" size="large" onClick={() => setEditingItem(null)}>Concluir</Button>
        </Box>
      </Drawer>
    </Box>
  )
}

export default FlowEditor
