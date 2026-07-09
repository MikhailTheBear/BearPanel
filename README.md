<p align="center">
  <a href="https://github.com/MikhailTheBear/BearPanel" target="_blank">
    <img src="https://raw.githubusercontent.com/MikhailTheBear/BearPanel/main/public/icon.svg" width="300" alt="BearPanel Logo">
  </a>
</p>

<p align="center">
  <b>BearPanel</b> — Minecraft server control panel built with Laravel & Docker
</p>

<p align="center">
  <img src="https://img.shields.io/github/stars/MikhailTheBear/BearPanel?style=flat-square">
  <img src="https://img.shields.io/github/issues/MikhailTheBear/BearPanel?style=flat-square">
  <img src="https://img.shields.io/github/license/MikhailTheBear/BearPanel?style=flat-square">
</p>

---

## 🐻 About BearPanel

**BearPanel** is a lightweight self-hosted control panel for managing Minecraft servers.  
Inspired by Pterodactyl, but focused on simplicity and learning.

Features:

- 🎮 Start / Stop / Restart Minecraft servers  
- 📂 File manager (upload, edit, delete, folders)  
- 💻 Live console (WebSockets)  
- ⚙️ Startup configuration (Java, jar, RAM, command)  
- 🐳 Docker-based runtime  
- 👤 User & Admin panel  
- 🌐 LAN / public access support  

---

## 🚀 Installation

```
curl -s -o install.sh https://hm337566.webhm.cloud/bearpanel/install.sh && sudo bash install.sh
```

---

## 🧠 How it works

- Each server runs inside a Docker container  
- Console uses WebSockets (Laravel Reverb)  
- Files stored in storage/app/servers/{uuid}  
- Startup command supports variables:
```
{{RAM}} {{JAR}} {{UUID}} {{SERVER_NAME}}
```
---

## 🔐 Security

This project is for educational and private use.  
Do NOT expose publicly without authentication & firewall.

---

## 🧪 Status

This project is under active development and may change.

---

## ❤️ Author

Built with love by **MikhailTheBear** 🐻
