import Button from "@material-ui/core/Button"
import TextField from "@material-ui/core/TextField"
import Dialog from "@material-ui/core/Dialog"
import DialogActions from "@material-ui/core/DialogActions"
import DialogContent from "@material-ui/core/DialogContent"
import DialogTitle from "@material-ui/core/DialogTitle"
import useFormValues from "@h/useFormValues"
import React, { useState, ChangeEvent, MouseEvent } from "react"
import Alert from "@material-ui/core/Alert"
import InputLabel from "@material-ui/core/InputLabel"
import FormControl from "@material-ui/core/FormControl"
import Select from "@material-ui/core/Select"
import { SelectChangeEvent } from "@material-ui/core/Select"
import Chip from "@material-ui/core/Chip"
import Box from "@material-ui/core/Box"
import axios from "axios"
import OutlinedInput from "@material-ui/core/OutlinedInput"

import MenuItem from "@material-ui/core/MenuItem"
export default function CreateModat({
  open,
  handleClose,
  handleSubmit,
  errors,
  showErorrs,
  cats,
}: {
  cats: []
  open: boolean
  handleClose: () => void
  handleSubmit: (
    values: PhotoInterface,
    resetValues: () => void,
    resetUploadedFile: () => void
  ) => Promise<void>
  showErorrs?: boolean
  errors?: { [key: string]: string }
}) {
  const [values, handleChange, setValues, resetFormValues] = useFormValues({
    name: "",
    cats: [],
  })

  const fileInitialValue = {
    name: "",
    imgUrl: undefined,
  }

  const [uploadedFile, setUploadedFile] = useState(fileInitialValue)

  const handleChangeFile = async (
    e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    let file
    let target = e.target as HTMLInputElement
    if (target.files) {
      file = target.files[0]
    } else {
      return
    }

    let data = new FormData()
    data.append("file", file)

    axios.post(route("photos.store-file"), data).then((res) => {
      setUploadedFile(res.data)
      setValues({
        ...values,
        url: res.data.name,
      })
    })
  }

  const getItemName = (items: [], id: number) => {
    let item: { id: number; name: string } | undefined = items.find(
      (e: { id: number }) => e.id == id
    )
    if (item && item["name"]) {
      return item["name"]
    }
    return false
  }

  const getItemsByIds = (items: [], ids: []) => {
    return items.filter((e: { id: number }) =>
      ids.find((id: number) => e.id == id)
    )
  }

  function handleChangeSelect(e: SelectChangeEvent<any>) {
    const catsIds = e.target.value as unknown as []
    let currentItems = getItemsByIds(cats, catsIds)

    setValues((values: PhotoInterface) => ({
      ...values,
      cats: currentItems,
    }))
  }

  function resetUploadedFile() {
    setUploadedFile(fileInitialValue)
  }

  return (
    <div>
      <Dialog open={open} onClose={handleClose}>
        <DialogTitle>Create new Photo</DialogTitle>
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
            label="Photo name"
            type="text"
            fullWidth
            variant="standard"
          />
          <input type="hidden" name="url" value={uploadedFile.name}></input>
          {uploadedFile.imgUrl && (
            <img
              src={uploadedFile.imgUrl}
              alt="Uploaded img"
              width={400}
              height={300}
            />
          )}
          <TextField
            onChange={(e) => handleChangeFile(e)}
            name="img"
            margin="dense"
            id="img"
            label="Image"
            type="file"
            fullWidth
            variant="standard"
            value={values.img}
          />

          <FormControl sx={{ m: 1, width: 300 }}>
            <InputLabel id="multiple-tags-label">Categories</InputLabel>
            <Select
              name="tags"
              labelId="multiple-tags-label"
              id="multiple-tags"
              multiple
              value={values.cats.map((e: { id: number; name: string }) => e.id)}
              onChange={(e) => handleChangeSelect(e)}
              input={<OutlinedInput id="select-multiple-chip" label="Tags" />}
              renderValue={(selected) => (
                <Box sx={{ display: "flex", flexWrap: "wrap" }}>
                  {selected.map((id: number) => (
                    <Chip
                      key={id}
                      label={getItemName(cats, id)}
                      sx={{ m: "2px" }}
                    />
                  ))}
                </Box>
              )}
            >
              {cats.map((item: { id: number; name: string }) => (
                <MenuItem key={item.id} value={item.id}>
                  {item.name}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleClose}>Cancel</Button>
          <Button
            onClick={() =>
              handleSubmit(values, resetFormValues, resetUploadedFile)
            }
          >
            Save
          </Button>
        </DialogActions>
      </Dialog>
    </div>
  )
}
