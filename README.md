# Spotify Tracks Database - COMP4018 2026

Proyecto final de base de datos utilizando el dataset [Spotify Tracks Dataset](https://www.kaggle.com/datasets/maharshipandya/-spotify-tracks-dataset) de Kaggle.

## Dataset
- **Fuente:** [Kaggle - Spotify Tracks Dataset](https://www.kaggle.com/datasets/maharshipandya/-spotify-tracks-dataset)
- **Filas:** 114,000 tracks
- **Columnas:** 21 atributos por track (audio features, artistas, géneros, álbumes)

---

## Tecnologías utilizadas
- **Base de datos:** MySQL (XAMPP / MariaDB)
- **Backend:** PHP con PDO
- **Frontend:** HTML, CSS
- **Preprocesamiento:** Python (pandas)

---

## Estructura del repositorio

```
proyectofinal/
├── preprocesamiento/
│   ├── preprocessing.py        # Script Python CSV → SQL
│   ├── spotify_database.sql    # SQL completo con tablas, datos, índices y triggers
│   └── output_csvs/            # CSVs separados por tabla generados por el script
│       ├── track.csv
│       ├── album.csv
│       ├── artist.csv
│       ├── genre.csv
│       ├── audio_features.csv
│       ├── track_artist.csv
│       └── track_genre.csv
└── web/
    ├── db.php                  # Conexión a MySQL
    ├── index.php               # Dashboard principal
    ├── login.php               # Autenticación de usuarios
    ├── register.php            # Registro de usuarios
    ├── logout.php              # Cerrar sesión
    ├── tracks.php              # Ver, insertar y modificar tracks
    ├── playlists.php           # Ver y crear playlists
    ├── playlist_track.php      # Detalle de playlist y añadir tracks
    ├── queries.php             # 5 queries SQL del proyecto
    ├── diagrams.php            # Diagramas E/R, relacional y atributos
    ├── nav.php                 # Barra de navegación reutilizable
    ├── style.css               # Estilos
    └── img/
        ├── er_conceptual.png   # Diagrama E/R conceptual
        └── modelo_relacional.png # Modelo relacional
```

---

## Modelo de datos

### Entidades (8)
| Entidad | Descripción | Origen |
|---|---|---|
| Track | Canción con sus atributos básicos | CSV |
| AudioFeatures | Características de audio de cada track (entidad débil) | CSV |
| Album | Álbum al que pertenece el track | CSV |
| Artist | Artista que interpreta el track | CSV |
| Genre | Género musical del track | CSV |
| User | Usuario de la aplicación web | App web |
| Playlist | Playlist creada por un usuario | App web |
| PublicPlaylist / PrivatePlaylist | Subtipos de Playlist — herencia ISA total, disjunta | App web |

### Relaciones (8)
| Relación | Tipo | Tabla intermedia |
|---|---|---|
| Track — Album | N:1 | — |
| Track — AudioFeatures | 1:1 | — |
| Track — Artist | M:N | TrackArtist |
| Track — Genre | M:N | TrackGenre |
| User — Playlist | 1:N | — |
| Playlist — Track | M:N | PlaylistTrack |
| Playlist — PublicPlaylist | ISA | — |
| Playlist — PrivatePlaylist | ISA | — |

---

## Normalización
| Forma Normal | Resultado |
|---|---|
| 1NF | El campo `artists` del CSV tenía múltiples valores separados por `;` → se crearon TrackArtist, TrackGenre y PlaylistTrack |
| 2NF | Sin violaciones — no hay dependencias parciales |
| 3NF | Sin violaciones — no hay dependencias transitivas |
| BCNF | `username` en User se declara UNIQUE por ser clave candidata |

---

## Triggers
| Trigger | Evento | Descripción |
|---|---|---|
| `trg_validate_popularity_insert` | BEFORE INSERT en Track | Valida popularity ∈ [0,100] y duration_ms > 0 |
| `trg_validate_popularity_update` | BEFORE UPDATE en Track | Valida popularity ∈ [0,100] y duration_ms > 0 |
| `trg_delete_track_cascade` | BEFORE DELETE en Track | Elimina filas en TrackArtist, TrackGenre, AudioFeatures y PlaylistTrack |

---

## Queries implementados
| # | Tipo requerido | Descripción |
|---|---|---|
| Q1 | JOIN 3+ tablas | Tracks con artistas y álbum filtrados por popularidad mínima |
| Q2 | GROUP BY / HAVING | Géneros con energía promedio mayor a un umbral |
| Q3 | Subconsulta con WITH | Tracks más populares que el promedio de su género |
| Q4 | Agregación | Top 10 artistas con más tracks |
| Q5 | JOIN 3+ tablas + HAVING | Álbumes con tracks explícitos |

---

## Cómo ejecutar el proyecto

### 1. Preprocesamiento
```bash
cd preprocesamiento
pip install pandas
python preprocessing.py
```
Genera `spotify_database.sql` y los CSVs en `output_csvs/`.

### 2. Cargar la base de datos en MySQL
```bash
cd c:\xampp\mysql\bin
mysql -u root -p < "ruta/a/spotify_database.sql"
```

### 3. Ejecutar la app web
1. Copiar la carpeta `web/` a `C:\xampp\htdocs\spotify`
2. Iniciar Apache y MySQL en XAMPP
3. Entrar a `http://localhost/spotify`

---

## Bonos implementados
-  Autenticación de usuarios (login, registro, sesiones PHP)

---

## Autor
- **Nombre:** Taix Pacheco Garcia
- **Curso:** COMP4018 — 2026
- **Dataset:** https://www.kaggle.com/datasets/maharshipandya/-spotify-tracks-dataset
