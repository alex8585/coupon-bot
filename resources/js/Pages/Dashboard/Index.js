import AdminLayout from "@l/AdminLayout.js"
import React, { useState, useEffect } from "react"
import Box from "@material-ui/core/Box"

import Paper from "@material-ui/core/Paper"

import Button from "@material-ui/core/Button"

const Dashbord = () => {
  return (
    <AdminLayout title="Dashbord">
      <Box sx={{ width: "100%" }}>
        <div>
          <Button variant="contained">Create</Button>
        </div>

        <Paper sx={{ width: "100%", mb: 2 }}>1111</Paper>
      </Box>
    </AdminLayout>
  )
}

export default Dashbord
