import React, { useState, useEffect } from 'react'
import { ThemeProvider, createTheme, CssBaseline, Box, Container, AppBar, Toolbar, Typography, Button, IconButton, Tooltip } from '@mui/material'
import LayoutIcon from '@mui/icons-material/Dashboard'
import AddIcon from '@mui/icons-material/Add'
import UploadFileIcon from '@mui/icons-material/UploadFile'
import ExitToAppIcon from '@mui/icons-material/ExitToApp'
import FlowList from './components/FlowList'
import FlowEditor from './components/FlowEditor'
import FlowImport from './components/FlowImport'

const theme = createTheme({
  palette: {
    mode: 'dark',
    primary: {
      main: '#6366f1', // Indigo 500
    },
    background: {
      default: '#0f172a', // Slate 900
      paper: '#1e293b',   // Slate 800
    },
    text: {
      primary: '#f8fafc',
      secondary: '#94a3b8',
    },
  },
  typography: {
    fontFamily: '"Inter", "Roboto", "Helvetica", "Arial", sans-serif',
    h1: { fontWeight: 800 },
    h3: { fontWeight: 700 },
  },
  components: {
    MuiButton: {
      styleOverrides: {
        root: {
          borderRadius: 12,
          textTransform: 'none',
          fontWeight: 600,
        },
      },
    },
    MuiCard: {
      styleOverrides: {
        root: {
          borderRadius: 16,
          backgroundImage: 'none',
          backgroundColor: '#1e293b',
          border: '1px solid rgba(255, 255, 255, 0.05)',
        },
      },
    },
  },
})

function App() {
  const [view, setView] = useState('list') // 'list' or 'editor'
  const [id, setId] = useState(null)
  const [metadata, setMetadata] = useState(null)
  const [csrfToken, setCsrfToken] = useState('')

  useEffect(() => {
    fetch(`../../marketplace/flow/front/api.php?action=get_metadata&_t=${new Date().getTime()}`)
      .then(res => res.json())
      .then(data => {
        setMetadata(data)
        setCsrfToken(data.csrf_token)
      })
  }, [])

  const handleEditFlow = (flowId) => {
    setId(flowId)
    setView('editor')
  }

  const handleCreateFlow = () => {
    setId(null)
    setView('editor')
  }

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <Box sx={{ minHeight: '100vh', py: 4 }}>
        <Container maxWidth="lg">
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 6 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
              <Box sx={{ p: 1.5, bgcolor: 'primary.main', borderRadius: 3, boxShadow: '0 8px 16px -4px rgba(99, 102, 241, 0.4)' }}>
                <LayoutIcon sx={{ fontSize: 32, color: 'white' }} />
              </Box>
              <Box>
                <Typography variant="h4" sx={{ fontWeight: 800, color: 'white' }}>Flow Manager</Typography>
                <Typography variant="body2" sx={{ color: 'text.secondary' }}>Automação inteligente para GLPI</Typography>
              </Box>
              <Tooltip title="Voltar ao GLPI">
                  <IconButton 
                    onClick={() => window.location.href = '/front/central.php'}
                    sx={{ color: 'text.secondary', ml: 1, '&:hover': { color: 'white', bgcolor: 'rgba(255,255,255,0.1)' } }}
                  >
                    <ExitToAppIcon />
                  </IconButton>
              </Tooltip>
            </Box>
            

            {view === 'list' && (
              <Box sx={{ display: 'flex', gap: 2 }}>
                  <Button 
                    variant="outlined" 
                    startIcon={<UploadFileIcon />}
                    onClick={() => setView('import')}
                    sx={{ px: 3, py: 1.5, borderColor: 'rgba(255,255,255,0.2)', color: 'text.secondary' }}
                  >
                    Importar
                  </Button>
                  <Button 
                    variant="contained" 
                    startIcon={<AddIcon />}
                    onClick={handleCreateFlow}
                    sx={{ px: 3, py: 1.5 }}
                  >
                    Novo Fluxo
                  </Button>
              </Box>
            )}
          </Box>

          <main>
            {view === 'list' ? (
              <FlowList onEdit={handleEditFlow} csrfToken={csrfToken} />
            ) : view === 'import' ? (
               <FlowImport 
                  metadata={metadata} 
                  csrfToken={csrfToken}
                  onBack={() => { setView('list') }}
               />
            ) : (
              <FlowEditor 
                id={id} 
                metadata={metadata} 
                csrfToken={csrfToken}
                onBack={() => { setId(null); setView('list'); }} 
              />
            )}
          </main>
        </Container>
      </Box>
    </ThemeProvider>
  )
}

export default App
