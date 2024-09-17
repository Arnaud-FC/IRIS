import { useState } from 'react'
import { Box,Typography, TextField, Snackbar, Button } from '@mui/material';
import { useForm } from 'react-hook-form';
import './App.css'

function App() {


  const { register, handleSubmit, formState: { errors }, reset } = useForm();


  return (
    <>
    <h2>Se connecter </h2>
      <form onSubmit={handleSubmit()}>
      <Box
      component="form"
      sx={{ '& .MuiTextField-root': { m: 1, width: '25ch' } }}
      noValidate
      autoComplete="off"
      >
      <TextField id="outlined-basic" label="Email" variant="outlined" 
      {...register("email", {
        required: true,
        pattern: {
          value: /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/,
          message: "Veuillez saisir un email valide",
        }
      })}
      error={!!errors.email}
      helperText={errors.email ? errors.email.message : ""}
      />
      
      
      <TextField id="outlined-basic" label="Password" variant="outlined" 
        {...register("password", {required: true})}
        error={!!errors.password}
        helperText={errors.password ? errors.password.message : ""}
      />
      
      
      </Box>
      <Button variant="text" type='submit'>Se connecter</Button>
      </form>
    </>
  )
}

export default App
