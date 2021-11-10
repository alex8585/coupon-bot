import Button from "@material-ui/core/Button"
import TextField from "@material-ui/core/TextField"
import Dialog from "@material-ui/core/Dialog"
import DialogActions from "@material-ui/core/DialogActions"
import DialogContent from "@material-ui/core/DialogContent"
import DialogTitle from "@material-ui/core/DialogTitle"

import Alert from "@material-ui/core/Alert"
import React, { ChangeEvent,Dispatch } from "react"
export default function EditModal({
  open,
  handleClose,
  handleSubmit,
  currentRow,
  setCurrentRow,
  errors,
  showErorrs,
}:{
    currentRow:UserInterface
    setCurrentRow: Dispatch<React.SetStateAction<{}>>
    open:boolean
    handleClose:()=> void
    handleSubmit:() => void
    showErorrs?:boolean
    errors?:{[key: string]: string}
}) {
  const handleChangeRow = (e: ChangeEvent<any>) => {
    const key = e.target.name
    const value = e.target.value
    setCurrentRow({
      ...currentRow,
      [key]: value,
    })
  }

  return (
    <div>
      <Dialog open={open} onClose={handleClose}>
        <DialogTitle>Edit Images Category</DialogTitle>
         {errors && showErorrs && Object.keys(errors).length !== 0 && (
          <Alert severity="error">
            {errors && Object.keys(errors).map((keyName, i) => (
              <div key={i}>{errors[keyName]}</div>
            ))}
          </Alert>
        )}
        <DialogContent>
          {/* <DialogContentText>Create new tag</DialogContentText> */}
          <TextField
            onChange={(e) => handleChangeRow(e)}
            name="name"
            margin="dense"
            id="name"
            label="Category name"
            type="text"
            fullWidth
            variant="standard"
            value={currentRow.name}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={handleClose}>Cancel</Button>
          <Button onClick={() => handleSubmit()}>Save</Button>
        </DialogActions>
      </Dialog>
    </div>
  )
}
