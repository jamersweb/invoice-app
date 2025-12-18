import './App.css'
import './custom.css'
import Pages from "@/pages/index.jsx"
import './custom-js/custom-index.js'
import { Toaster } from "@/components/ui/toaster"

function App() {
  return (
    <>
      <Pages />
      <Toaster />
    </>
  )
}

export default App 