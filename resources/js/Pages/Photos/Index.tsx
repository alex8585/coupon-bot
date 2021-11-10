import Box from "@material-ui/core/Box"
import Table from "@material-ui/core/Table"
import TableBody from "@material-ui/core/TableBody"
import TableCell from "@material-ui/core/TableCell"
import TableContainer from "@material-ui/core/TableContainer"
import TablePagination from "@material-ui/core/TablePagination"
import TableRow from "@material-ui/core/TableRow"
import Paper from "@material-ui/core/Paper"
import Alert from "@material-ui/core/Alert"
import Button from "@material-ui/core/Button"

import AdminLayout from "@l/AdminLayout"
import { makeStyles } from "@material-ui/styles"
import CreateModat from "./CreateModal"
import DeleteConfirmModal from "@c/Admin/DeleteConfirmModal"
import EditModal from "./EditModal"
import AdminTableHead from "@c/Admin/AdminTableHead"
import { usePage } from "@inertiajs/inertia-react"

import React, { useState, useEffect, ChangeEvent, MouseEvent } from "react"
import { Inertia, RequestPayload, Page, ActiveVisit } from "@inertiajs/inertia"
const useStyles = makeStyles((theme) => ({
  topBtnsWrapp: {
    margin: "15px 0",
  },
  actionButton: {
    "& .MuiButton-root.MuiButton-contained.MuiButton-containedPrimary": {
      margin: "0px 5px",
    },
  },
}))

const headCells = [
  {
    id: "id",
    sortable: true,
    label: "ID",
  },
  {
    id: "name",
    sortable: true,
    label: "Name",
  },
  {
    id: "img",
    sortable: false,
    label: "Photo",
  },
  {
    id: "created_at",
    sortable: true,
    label: "Created at",
  },
  {
    id: "actions",
    sortable: false,
    label: "Actions",
  },
]

let timeout: NodeJS.Timeout

const Photos = () => {
  const [itemsQuery, setItemsQuery] = useState({
    page: 1,
    perPage: 5,
    direction: "asc",
    sort: "name",
  })

  let { page, perPage, direction, sort } = itemsQuery

  const [showErorrs, setShowErrors] = useState(false)

  function handleShowErrors() {
    if (timeout) {
      clearTimeout(timeout)
    }
    setShowErrors(true)
    timeout = setTimeout(() => {
      setShowErrors(false)
    }, 5000)
  }

  useEffect(() => {
    Inertia.get(route(route().current()), itemsQuery, {
      replace: true,
      preserveState: true,
    })
  }, [itemsQuery])

  const classes = useStyles()

  const {
    cats,
    items: { data: items },
    items: { total },
    flash: { success },
    flash: { error },
    errors,
  } = usePage().props as PagePropsType

  //console.log(items);

  // Avoid a layout jump when reaching the last page with empty items.
  const emptyRows = page > total / perPage ? perPage - (total % perPage) : 0

  console.log(page, perPage, total, emptyRows)
  const handleRequestSort = (
    event: ChangeEvent<HTMLInputElement>,
    newSort: string
  ) => {
    const isAsc = sort === newSort && direction === "asc"
    const newOrder = isAsc ? "desc" : "asc"
    setItemsQuery({
      ...itemsQuery,
      direction: newOrder,
      sort: newSort,
    })
  }

  const handleChangePage = (
    event: MouseEvent<HTMLButtonElement> | null,
    newPage: number
  ) => {
    setItemsQuery({
      ...itemsQuery,
      page: newPage + 1,
    })
  }

  const handleChangeRowsPerPage = (event: ChangeEvent<HTMLInputElement>) => {
    let perPage = parseInt(event.target.value, 10)
    setItemsQuery({
      ...itemsQuery,
      perPage,
    })
  }

  const [openCreateModal, setOpenCreateModal] = useState(false)
  const [openDeleteConfirmModal, setOpenDeleteConfirmModal] = useState(false)
  const [openEditModal, setOpenEditModal] = useState(false)

  const [currentRow, setCurrentRow]: [
    PhotoInterface,
    React.Dispatch<React.SetStateAction<{}>>
  ] = useState({})

  const openCreateModalHandler = () => {
    setOpenCreateModal(true)
  }

  const closeCreateModalHandler = () => {
    setOpenCreateModal(false)
  }

  const createSubmitHanler = async (
    values: PhotoInterface,
    resetValues: () => void,
    resetUploadedFile: () => void
  ) => {
    let data = values as unknown as RequestPayload
    Inertia.post(route(route().current()), data, {
      replace: true,
      preserveState: true,
      onSuccess: (page: Page) => {
        setOpenCreateModal(false)
        resetValues()
        resetUploadedFile()
      },
      onFinish: (visit: ActiveVisit) => {
        handleShowErrors()
      },
    })
  }

  const handleOpenDeleteConfirmModal = (row: PhotoInterface) => {
    setCurrentRow(row)
    setOpenDeleteConfirmModal(true)
  }

  const closeDeleteConfirmModalHandler = () => {
    setOpenDeleteConfirmModal(false)
  }

  const handleDeleteConfirm = async () => {
    Inertia.delete(route("photos.destroy", currentRow.id), {
      replace: true,
      preserveState: true,
      onSuccess: (page: Page) => {
        setOpenDeleteConfirmModal(false)
      },
      onFinish: (visit: ActiveVisit) => {
        handleShowErrors()
      },
    })
  }

  const handleOpenEditModal = (row: PhotoInterface) => {
    setCurrentRow(row)
    setOpenEditModal(true)
  }

  const closeEditModalHandler = () => {
    setOpenEditModal(false)
  }

  const handleEditSubmit = async (resetUploadedFile: () => void) => {
    let data = currentRow as RequestPayload
    Inertia.put(route("photos.update", currentRow.id), data, {
      replace: true,
      preserveState: true,
      onSuccess: (page: Page) => {
        setOpenEditModal(false)
        resetUploadedFile()
      },
      onFinish: (visit: ActiveVisit) => {
        handleShowErrors()
      },
    })
  }

  return (
    <AdminLayout title="Photos">
      {showErorrs && error && <Alert severity="error">{error}</Alert>}
      {showErorrs && success && <Alert severity="success">{success}</Alert>}

      <CreateModat
        cats={cats}
        errors={errors}
        showErorrs={showErorrs}
        handleSubmit={createSubmitHanler}
        open={openCreateModal}
        handleClose={closeCreateModalHandler}
      />
      <DeleteConfirmModal
        title="Delete tag confirmation"
        currentRow={currentRow}
        handleConfirm={handleDeleteConfirm}
        open={openDeleteConfirmModal}
        handleClose={closeDeleteConfirmModalHandler}
      />

      <EditModal
        cats={cats}
        errors={errors}
        showErorrs={showErorrs}
        setCurrentRow={setCurrentRow}
        currentRow={currentRow}
        handleSubmit={handleEditSubmit}
        open={openEditModal}
        handleClose={closeEditModalHandler}
      />

      <Box sx={{ width: "100%" }}>
        <div className={classes.topBtnsWrapp}>
          <Button variant="contained" onClick={() => openCreateModalHandler()}>
            Create
          </Button>
        </div>

        <Paper sx={{ width: "100%", mb: 2 }}>
          <TableContainer>
            <Table
              sx={{ minWidth: 750 }}
              aria-labelledby="tableTitle"
              size={"medium"}
            >
              <AdminTableHead
                headCells={headCells}
                order={direction}
                orderBy={sort}
                onRequestSort={handleRequestSort}
                rowCount={items.length}
              />
              <TableBody>
                {items.slice().map((row: PhotoInterface) => {
                  return (
                    <TableRow hover role="checkbox" tabIndex={-1} key={row.id}>
                      <TableCell> {row.id}</TableCell>
                      <TableCell> {row.name}</TableCell>
                      <TableCell>
                        <img src={row.thumbnail}></img>
                      </TableCell>
                      <TableCell align="left">{row.created_at}</TableCell>
                      <TableCell className={classes.actionButton}>
                        <Button
                          variant="contained"
                          onClick={() => handleOpenEditModal(row)}
                        >
                          Edit
                        </Button>
                        <Button
                          variant="contained"
                          color="error"
                          onClick={() => handleOpenDeleteConfirmModal(row)}
                        >
                          Delete
                        </Button>
                      </TableCell>
                    </TableRow>
                  )
                })}
                {emptyRows > 0 && (
                  <TableRow
                    style={{
                      height: 69.5 * emptyRows,
                    }}
                  >
                    <TableCell colSpan={6} />
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            rowsPerPageOptions={[5, 10, 25]}
            component="div"
            count={total}
            rowsPerPage={perPage}
            page={page - 1}
            onPageChange={(e, newPage) => {
              handleChangePage(e, newPage)
            }}
            onRowsPerPageChange={handleChangeRowsPerPage}
          />
        </Paper>
      </Box>
    </AdminLayout>
  )
}

export default Photos
