const taskInput = document.getElementById("new-task");
const addTaskBtn = document.getElementById("add-task");
const taskList = document.getElementById("task-list");

// ---------- Cargar tareas al inicio ----------
window.addEventListener("load", loadTasks);

// ---------- Añadir tarea ----------
addTaskBtn.addEventListener("click", addTask);
taskInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") addTask();
    });

    function addTask() {
    const title = taskInput.value.trim();
    if (!title) return;

    const tasks = getTasks();
    const newTask = { id: Date.now(), title, completed: false };
    tasks.push(newTask);
    saveTasks(tasks);
    renderTasks(tasks);

    taskInput.value = "";
}

// ---------- Renderizar tareas ----------
function renderTasks(tasks) {
    taskList.innerHTML = "";

    if (tasks.length === 0) {
        taskList.innerHTML = `<li style="text-align:center; color:#888;">No hay tareas</li>`;
        updateProgressBar(0, 0);
        return;
    }

    tasks.forEach((task) => {
        const li = document.createElement("li");
        li.dataset.id = task.id;
        li.classList.toggle("completed", task.completed);
        li.innerHTML = `
        <span>${task.title}</span>
        <div>
            <button class="check-btn">${task.completed ? "↩" : "✔"}</button>
            <button class="delete-btn">🗑</button>
        </div>
        `;

        li.querySelector(".check-btn").addEventListener("click", () => toggleTask(task.id));
        li.querySelector(".delete-btn").addEventListener("click", () => deleteTask(task.id));

        taskList.appendChild(li);
    });

    // Actualiza la barra de progreso
    const completed = tasks.filter(t => t.completed).length;
    updateProgressBar(completed, tasks.length);
}

// ---------- Alternar completado ----------
function toggleTask(id) {
    const tasks = getTasks();
    const task = tasks.find((t) => t.id === id);
    if (task) task.completed = !task.completed;
    saveTasks(tasks);
    renderTasks(tasks);
}

// ---------- Eliminar tarea ----------
function deleteTask(id) {
    let tasks = getTasks();
    tasks = tasks.filter((t) => t.id !== id);
    saveTasks(tasks);
    renderTasks(tasks);
    }

// ---------- Guardar / cargar ----------
function saveTasks(tasks) {
    localStorage.setItem("tasks", JSON.stringify(tasks));
}

function getTasks() {
    return JSON.parse(localStorage.getItem("tasks")) || [];
}

function loadTasks() {
    const tasks = getTasks();
    renderTasks(tasks);
}

function updateProgressBar(completed, total) {
    const bar = document.querySelector(".progress-bar");
    const text = document.getElementById("progress-text");
    const count = document.getElementById("progress-count");
    const message = document.getElementById("progress-message");

    const percent = total === 0 ? 0 : Math.round((completed / total) * 100);

    // Actualizar barra y textos
    bar.style.width = `${percent}%`;
    text.textContent = `${percent}%`;
    count.textContent = `${completed} de ${total} tareas completadas`;

    // Cambiar color y mostrar mensaje
    if (percent === 100 && total > 0) {
        bar.classList.add("full");
        message.classList.remove("hidden");
        message.classList.add("visible");
    } else {
        bar.classList.remove("full");
        message.classList.add("hidden");
        message.classList.remove("visible");
    }
}

