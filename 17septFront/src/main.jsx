import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import ReactDOM from "react-dom/client";
import { createBrowserRouter, RouterProvider, Outlet } from 'react-router-dom'
import App from './App.jsx'
import './index.css'
import PageLogin from './components/PageLogin.jsx';
import Login from './components/Login.jsx';
import Register from './components/Register.jsx';

const route = createBrowserRouter([
  { path: 'login/', 
    element:(<PageLogin/>),
    children: [
      { path: '/login/login', element: <Login /> },
      { path: '/login/register', element: <Register /> },
    ]
 }
])

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <RouterProvider router={route}>
      <App />
    </RouterProvider>
  </StrictMode>,
)
