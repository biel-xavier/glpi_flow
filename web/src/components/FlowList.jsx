import React, { useState, useEffect } from 'react'
import { Grid, Card, CardContent, Typography, Box, Chip, IconButton, Button, Tooltip, Stack } from '@mui/material'
import EditIcon from '@mui/icons-material/Edit'
import DeleteIcon from '@mui/icons-material/Delete'
import EntityIcon from '@mui/icons-material/Business'
import CategoryIcon from '@mui/icons-material/Category'
import ActiveIcon from '@mui/icons-material/CheckCircle'
import InactiveIcon from '@mui/icons-material/Cancel'

function FlowList({ onEdit, csrfToken }) {
  const [flows, setFlows] = useState([])
  const [loading, setLoading] = useState(true)

  const fetchFlows = () => {
    fetch(`../../marketplace/flow/front/api.php?action=get_flows&_t=${new Date().getTime()}`)
      .then(res => res.json())
      .then(data => {
        setFlows(data)
        setLoading(false)
      })
  }

  useEffect(() => {
    fetchFlows()
  }, [])

  const handleDelete = async (id) => {
    if (!confirm('Tem certeza que deseja excluir este fluxo?')) return;

    try {
        const res = await fetch(`../../marketplace/flow/front/api.php?action=delete_flow&id=${id}`, {
            method: 'GET'
        });
        const data = await res.json();
        if (data.status === 'success') {
            fetchFlows();
        } else {
            alert('Erro ao excluir: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (e) {
        console.error(e);
        alert('Erro de conexão ao excluir.');
    }
  }

  const handleToggleActive = async (flow) => {
    const newStatus = flow.is_active == 1 ? 0 : 1;
    console.log('Toggling active. CSRF:', csrfToken);
    
    try {
        const res = await fetch(`../../marketplace/flow/front/api.php?action=toggle_active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Glpi-Csrf-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: flow.id, is_active: newStatus })
        });
        const data = await res.json();
        if (data.status === 'success') {
            fetchFlows();
        } else {
            alert('Erro ao atualizar status: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (e) {
        console.error(e);
        alert('Erro de conexão ao atualizar status.');
    }
  }

  if (loading) return <Box sx={{ textAlign: 'center', py: 10, color: 'text.secondary' }}>Carregando fluxos...</Box>

  return (
    <Grid container spacing={3}>
      {flows.length === 0 ? (
        <Grid item xs={12}>
          <Card sx={{ textAlign: 'center', py: 10 }}>
            <Typography variant="body1" color="text.secondary italic">
              Nenhum fluxo encontrado. Crie o seu primeiro para começar a automatizar!
            </Typography>
          </Card>
        </Grid>
      ) : (
        flows.map((flow) => (
          <Grid item xs={12} md={6} lg={4} key={flow.id}>
            <Card sx={{ height: '100%', display: 'flex', flexDirection: 'column', transition: 'transform 0.2s', '&:hover': { transform: 'translateY(-4px)' } }}>
              <CardContent sx={{ flexGrow: 1 }}>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
                  <Typography variant="caption" sx={{ fontFamily: 'monospace', color: 'primary.main', fontWeight: 'bold' }}>
                    ID: {flow.id}
                  </Typography>
                  <Tooltip title="Clique para alterar o status">
                    <Chip 
                        label={flow.is_active == 1 ? "Ativo" : "Inativo"} 
                        size="small"
                        onClick={() => handleToggleActive(flow)}
                        icon={flow.is_active == 1 ? <ActiveIcon /> : <InactiveIcon />}
                        color={flow.is_active == 1 ? "success" : "error"}
                        variant="outlined"
                        sx={{ borderRadius: 1.5, fontWeight: 'bold', fontSize: '10px', cursor: 'pointer' }}
                    />
                  </Tooltip>
                </Box>
                
                <Typography variant="h6" sx={{ fontWeight: 700, mb: 0.5, color: 'white' }}>
                  {flow.name}
                </Typography>
                
                <Typography variant="body2" sx={{ color: 'text.secondary', mb: 3, minHeight: 40, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                  {flow.description || 'Sem descrição definida.'}
                </Typography>

                <Stack spacing={1} sx={{ bgcolor: 'rgba(255,255,255,0.03)', p: 2, borderRadius: 2, mb: 2 }}>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                    <EntityIcon sx={{ fontSize: 16, color: 'primary.main' }} />
                    <Typography variant="caption" color="text.secondary">
                      Entidade: <b style={{ color: '#fff' }}>{flow.entities_id}</b>
                    </Typography>
                  </Box>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                    <CategoryIcon sx={{ fontSize: 16, color: 'primary.main' }} />
                    <Typography variant="caption" color="text.secondary">
                      Categoria: <b style={{ color: '#fff' }}>{flow.itilcategories_id}</b>
                    </Typography>
                  </Box>
                </Stack>
              </CardContent>

              <Box sx={{ p: 2, pt: 0, mt: 'auto', display: 'flex', gap: 1 }}>
                <Button 
                  fullWidth 
                  variant="outlined" 
                  startIcon={<EditIcon />}
                  onClick={() => onEdit(flow.id)}
                >
                  Editar
                </Button>
                <IconButton 
                    color="error" 
                    sx={{ bgcolor: 'rgba(244, 67, 54, 0.05)', borderRadius: 2 }}
                    onClick={() => handleDelete(flow.id)}
                >
                  <DeleteIcon fontSize="small" />
                </IconButton>
              </Box>
            </Card>
          </Grid>
        ))
      )}
    </Grid>
  )
}

export default FlowList
