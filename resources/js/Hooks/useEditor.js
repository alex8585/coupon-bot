import { useState } from 'react';

export default function useEditor(defalt) {  
    const [value, setValue] = useState(defalt);


    function handleChangEditor(event, editor) {
        const data = editor.getData();
        setValue(data);
    }

    // function setCurrent(value) {
    //     setValue(value);
    // }
    
    function resetEditor() {
        setValue('');
    }
    return [value, handleChangEditor, resetEditor];
}