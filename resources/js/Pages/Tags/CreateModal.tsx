import Button from "@material-ui/core/Button"
import TextField from "@material-ui/core/TextField"
import Dialog from "@material-ui/core/Dialog"
import DialogActions from "@material-ui/core/DialogActions"
import DialogContent from "@material-ui/core/DialogContent"
import DialogTitle from "@material-ui/core/DialogTitle"
import useFormValues from "@h/useFormValues"

import Alert from "@material-ui/core/Alert"
export default function CreateModat({
  open,
  handleClose,
  handleSubmit,
  errors,
  showErorrs,
}:{
    open:boolean
    handleClose:()=> void
    handleSubmit:(values: UserInterface, resetValues: () => void) => Promise<void>
    showErorrs?:boolean
    errors?:{[key: string]: string}

}) {
  const [values, handleChange, setValues, resetFormValues] = useFormValues({
    name: "",
  })

  return (
    <div>
      <Dialog open={open} onClose={handleClose}>
        <DialogTitle>Create new tag</DialogTitle>
        {errors && showErorrs && Object.keys(errors).length !== 0 && (
          <Alert severity="error">
            {errors &&
              Object.keys(errors).map((keyName, i) => (
                <div key={i}>{errors[keyName]}</div>
              ))}
          </Alert>
        )}
        <DialogContent>
          {/* <DialogContentText>Create new tag</DialogContentText> */}
          <TextField
            value={values.name}
            onChange={(e) => handleChange(e)}
            name="name"
            margin="dense"
            id="name"
            label="Tag name"
            type="text"
            fullWidth
            variant="standard"
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={handleClose}>Cancel</Button>
          <Button onClick={() => handleSubmit(values, resetFormValues)}>
            Save
          </Button>
        </DialogActions>
      </Dialog>
    </div>
  )
}
