import Button from "@material-ui/core/Button"
import TextField from "@material-ui/core/TextField"
import Dialog from "@material-ui/core/Dialog"
import DialogActions from "@material-ui/core/DialogActions"
import DialogContent from "@material-ui/core/DialogContent"
import DialogTitle from "@material-ui/core/DialogTitle"
import Alert from "@material-ui/core/Alert"
import React, { useState, ChangeEvent, Dispatch } from "react"
import InputLabel from "@material-ui/core/InputLabel"
import FormControl from "@material-ui/core/FormControl"
import Select from "@material-ui/core/Select"
import Chip from "@material-ui/core/Chip"
import Box from "@material-ui/core/Box"
import OutlinedInput from "@material-ui/core/OutlinedInput"
import MenuItem from "@material-ui/core/MenuItem"
import axios from "axios"
import { SelectChangeEvent } from "@material-ui/core/Select"
export default function EditModal({
  open,
  handleClose,
  handleSubmit,
  currentRow,
  setCurrentRow,
  errors,
  showErorrs,
  cats,
}: {
  currentRow: PhotoInterface
  setCurrentRow: Dispatch<React.SetStateAction<{}>>
  open: boolean
  handleClose: () => void
  handleSubmit: (resetUploadedFile: () => void) => Promise<void>
  showErorrs?: boolean
  errors?: { [key: string]: string }
  cats: []
}) {
  const handleChangeRow = (e: ChangeEvent<any>) => {
    const key = e.target.name
    const value = e.target.value
    setCurrentRow({
      ...currentRow,
      [key]: value,
    })
  }

  const fileInitialValue = {
    name: "",
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
      setCurrentRow({
        ...currentRow,
        url: res.data.name,
        imgUrl: res.data.imgUrl,
      })
    })
  }

  function resetUploadedFile() {
    setUploadedFile(fileInitialValue)
  }

  function handleCloseModal() {
    resetUploadedFile()
    handleClose()
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

  function handleChangeSelect(e: SelectChangeEvent<any[]>) {
    const catsIds = e.target.value as []
    let currentItems = getItemsByIds(cats, catsIds)
    setCurrentRow((currentRow) => ({
      ...currentRow,
      categories: currentItems,
    }))
  }

  return (
    <div>
      <Dialog open={open} onClose={handleCloseModal}>
        <DialogTitle>Edit Photo</DialogTitle>
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
            onChange={(e) => handleChangeRow(e)}
            name="name"
            margin="dense"
            id="name"
            label="Tag name"
            type="text"
            fullWidth
            variant="standard"
            value={currentRow.name}
          />
          <input type="hidden" name="url" value={uploadedFile.name}></input>
          {currentRow.imgUrl && (
            <img
              src={currentRow.imgUrl}
              alt="Uploaded img"
              width={400}
              height={300}
            />
          )}
          <TextField
            onChange={(e) => handleChangeFile(e)}
            margin="dense"
            id="img"
            label="Image"
            type="file"
            fullWidth
            variant="standard"
          />
          <FormControl sx={{ m: 1, width: 300 }}>
            <InputLabel id="multiple-tags-label">Tags</InputLabel>
            <Select
              name="tags"
              labelId="multiple-tags-label"
              id="multiple-tags"
              multiple
              value={
                currentRow.categories &&
                currentRow.categories.map((e: { id: number }) => e.id)
              }
              onChange={(e) => handleChangeSelect(e)}
              input={<OutlinedInput id="select-multiple-chip" label="Tags" />}
              renderValue={(selected) => (
                <Box sx={{ display: "flex", flexWrap: "wrap" }}>
                  {selected.map((id) => (
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
          <Button onClick={handleCloseModal}>Cancel</Button>
          <Button onClick={() => handleSubmit(resetUploadedFile)}>Save</Button>
        </DialogActions>
      </Dialog>
    </div>
  )
}
