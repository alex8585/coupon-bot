import { useState, useEffect } from "react"

export default function useFormValues(initialState) {
  const [values, setValues] = useState(initialState)

  function handleChange(e) {
    const key = e.target.name
    let value = e.target.value

    if (e.target.type === "checkbox") {
      value = e.target.checked
    }

    setValues((values) => ({
      ...values,
      [key]: value,
    }))
  }

  function resetFormValues() {
    setValues((values) => ({
      ...initialState,
    }))
  }

  return [values, handleChange, setValues, resetFormValues]
}
