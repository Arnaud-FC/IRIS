import React from 'react'
import { useState } from 'react';
import { Link, Outlet } from 'react-router-dom'
import { Button } from '@mui/material';


export default function PageLogin() {

    const [varianteLogin, setVarianteLogin] = useState("contained")
    const [varianteRegister, setVarianteRegister] = useState("outlined")

    const clickLogin = () => {
        setVarianteLogin('contained')
        setVarianteRegister('outlined')
    }
    const clickRegister = () => {
        setVarianteRegister('contained')
        setVarianteLogin('outlined')
    }



  return (
    <>
        <div id='login'> 
            <span id='login'> <Link to="/login/login">
            <Button variant={varianteLogin} onClick={clickLogin}>Se Connecter</Button> 
            </Link> </span>
            || 
            <span id='register'> <Link to="/login/register">
            <Button variant={varianteRegister} onClick={clickRegister}>S'inscrire</Button>  
             </Link></span>  
        </div>

        <div>
          <Outlet />
        </div>
        
    </>
  )
}
