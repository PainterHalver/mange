import React from "react";
import { Routes, Route, Navigate } from "react-router-dom";
import Register from "./Register";
import Login from "./Login";

const Auth = () => {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/signup" element={<Register />} />
      <Route path="/*" element={<Navigate to="/login" />} />
    </Routes>
  );
};

export default Auth;
