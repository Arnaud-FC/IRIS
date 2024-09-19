import React from 'react';
import { useState } from 'react';
import { Box,Typography, TextField, Snackbar, Button } from '@mui/material';
import { useForm } from 'react-hook-form';
import axios from 'axios';
import { redirect, useNavigate } from 'react-router-dom';

export default function Register() {

  const { register, handleSubmit, formState: { errors }, reset, setError } = useForm();

  const navigate = useNavigate();

  const [snackbarOpen, setSnackbarOpen] = useState(false);
  const handleSnackbarOpen = () => setSnackbarOpen(true);
  const handleSnackbarClose = () => setSnackbarOpen(false);

  const [snackbarMessage, setSnackbarMessage] = useState("");

    const sendToBack = (data) => {
        console.log(data)
        axios.post('https://127.0.0.1:8000/user/register', data)
        .then((response) => {console.log(response.data.token)
            setSnackbarMessage('Vous etes inscrit')
            handleSnackbarOpen()
            return navigate("/login/login")
        })
        .catch(error => {
            console.log(error?.response?.data?.message);
            setError('email', { type: 'error', message: error.response.data.message });
            setError('password', { type: 'error', message: error.response.data.message });
        })
    }


  return (
    <>
      <form onSubmit={handleSubmit(sendToBack)}>
      <Box
      component="form"
      sx={{ '& .MuiTextField-root': { m: 3, width: '25ch' } }}
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
      <Snackbar
        anchorOrigin={{ vertical: "top", horizontal: "center" }}
        open={snackbarOpen}
        onClose={handleSnackbarClose}
        message={snackbarMessage}
        key={"top" + "center"}
      />
      <Button variant="text" type='submit'>S'inscrire</Button>
      </form>
    </>
  )
}

