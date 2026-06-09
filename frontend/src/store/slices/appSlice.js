import { createSlice } from '@reduxjs/toolkit'

const initialState = {
  user: null,
  token: localStorage.getItem('token'),
  sidebarOpen: true,
  activeTicketsCount: 3,
  notifications: [
    { id: 1, message: 'Welcome to your new workspace!', type: 'info', read: false },
    { id: 2, message: 'Oryxie Bot is connected to MySQL.', type: 'success', read: false },
  ],
}

export const appSlice = createSlice({
  name: 'app',
  initialState,
  reducers: {
    toggleSidebar: (state) => {
      state.sidebarOpen = !state.sidebarOpen
    },
    addNotification: (state, action) => {
      state.notifications.unshift({
        id: Date.now(),
        message: action.payload.message,
        type: action.payload.type || 'info',
        read: false,
      })
    },
    markAllNotificationsRead: (state) => {
      state.notifications.forEach(n => n.read = true)
    },
    setTicketsCount: (state, action) => {
      state.activeTicketsCount = action.payload
    },
    setCredentials: (state, action) => {
      state.user = action.payload.user
      state.token = action.payload.token
    },
    logout: (state) => {
      state.user = null
      state.token = null
      localStorage.removeItem('token')
    }
  },
})

export const { 
  toggleSidebar, 
  addNotification, 
  markAllNotificationsRead,
  setTicketsCount,
  setCredentials,
  logout
} = appSlice.actions

export default appSlice.reducer

