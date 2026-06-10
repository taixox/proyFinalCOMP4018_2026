import pandas as pd
import uuid
import os


INPUT_CSV = 'dataset.csv'          #archivo del dataset
OUTPUT_DIR = 'output_csvs'         #carpeta donde se guardan los CSVs
SQL_FILE = 'spotify_database.sql'  #nombre del archivo SQL final

os.makedirs(OUTPUT_DIR, exist_ok=True)


df = pd.read_csv(INPUT_CSV)

# Eliminar columna innecesaria
df = df.drop(columns=['Unnamed: 0'])

#Eliminar filas con valores nulos en columnas importantes
df = df.dropna(subset=['track_id', 'artists', 'album_name', 'track_name'])

print(f"Filas despues de limpiar nulos: {len(df)}")

# Eliminar duplicados por track_id
df = df.drop_duplicates(subset=['track_id'])
print(f"Filas despues de eliminar duplicados: {len(df)}")

# tabla Genre
genres = df['track_genre'].dropna().unique()
genre_df = pd.DataFrame({
    'genre_id': [str(uuid.uuid4()) for _ in genres],
    'genre_name': genres
})
genre_map = dict(zip(genre_df['genre_name'], genre_df['genre_id']))
genre_df.to_csv(f'{OUTPUT_DIR}/genre.csv', index=False)
print(f"  Generos unicos: {len(genre_df)}")

# tabla Album
albums = df['album_name'].dropna().unique()
album_df = pd.DataFrame({
    'album_id': [str(uuid.uuid4()) for _ in albums],
    'album_name': albums
})
album_map = dict(zip(album_df['album_name'], album_df['album_id']))
album_df.to_csv(f'{OUTPUT_DIR}/album.csv', index=False)
print(f"  Albums unicos: {len(album_df)}")

#tabkla Artist
all_artists = set()
for artists_str in df['artists'].dropna():
    for artist in artists_str.split(';'):
        all_artists.add(artist.strip())

artist_df = pd.DataFrame({
    'artist_id': [str(uuid.uuid4()) for _ in all_artists],
    'artist_name': list(all_artists)
})
artist_map = dict(zip(artist_df['artist_name'], artist_df['artist_id']))
artist_df.to_csv(f'{OUTPUT_DIR}/artist.csv', index=False)
print(f"  Artistas unicos: {len(artist_df)}")

# tabla Track
df['album_id'] = df['album_name'].map(album_map)
track_df = df[['track_id', 'track_name', 'duration_ms', 'explicit',
               'popularity', 'time_signature', 'album_id']].copy()
track_df['explicit'] = track_df['explicit'].astype(int)
track_df.to_csv(f'{OUTPUT_DIR}/track.csv', index=False)
print(f"  Tracks: {len(track_df)}")

#GenerAR tabla AudioFeatures
audio_df = df[['track_id', 'danceability', 'energy', 'key', 'loudness',
               'mode', 'speechiness', 'acousticness', 'instrumentalness',
               'liveness', 'valence', 'tempo']].copy()
audio_df.to_csv(f'{OUTPUT_DIR}/audio_features.csv', index=False)
print(f"  AudioFeatures: {len(audio_df)}")

# tabla Trackartist (relacion M:N)
track_artist_rows = []
for _, row in df.iterrows():
    if pd.isna(row['artists']):
        continue
    for artist in row['artists'].split(';'):
        artist = artist.strip()
        if artist in artist_map:
            track_artist_rows.append({
                'track_id': row['track_id'],
                'artist_id': artist_map[artist]
            })

track_artist_df = pd.DataFrame(track_artist_rows)
track_artist_df = track_artist_df.drop_duplicates()
track_artist_df.to_csv(f'{OUTPUT_DIR}/track_artist.csv', index=False)
print(f"  TrackArtist filas: {len(track_artist_df)}")

#Tabla TrackGenre(relacion M:N)
track_genre_df = df[['track_id', 'track_genre']].copy()
track_genre_df['genre_id'] = track_genre_df['track_genre'].map(genre_map)
track_genre_df = track_genre_df[['track_id', 'genre_id']].drop_duplicates()
track_genre_df.to_csv(f'{OUTPUT_DIR}/track_genre.csv', index=False)
print(f"  TrackGenre filas: {len(track_genre_df)}")

# Generar archivo sql
def escape(val):
    if pd.isna(val):
        return 'NULL'
    val = str(val).replace("'", "''").replace("\\", "\\\\")
    return f"'{val}'"
 
def write_inserts(f, table, df_table, columns):
    if df_table.empty:
        return
    batch_size = 500
    reserved = {'key', 'mode', 'value', 'name', 'status', 'index'}
    def col_name(c):
        c_clean = c.strip('`')
        if c_clean in reserved:
            return f'`{c_clean}`'
        return c_clean
    for i in range(0, len(df_table), batch_size):
        batch = df_table.iloc[i:i+batch_size]
        cols = ', '.join([col_name(c) for c in columns])
        f.write(f"INSERT INTO {table} ({cols}) VALUES\n")
        rows = []
        for _, row in batch.iterrows():
            vals = ', '.join([escape(row[c.strip('`')]) for c in columns])
            rows.append(f"  ({vals})")
        f.write(',\n'.join(rows) + ';\n\n')
 
with open(SQL_FILE, 'w', encoding='utf-8', newline='\n') as f:
    f.write("DROP DATABASE IF EXISTS spotify_db;\n")
    f.write("CREATE DATABASE spotify_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n")
    f.write("USE spotify_db;\n\n")
 
    f.write("""CREATE TABLE Album (
    album_id VARCHAR(36) PRIMARY KEY,
    album_name VARCHAR(255) NOT NULL
);\n\n""")
 
    f.write("""CREATE TABLE Artist (
    artist_id VARCHAR(36) PRIMARY KEY,
    artist_name VARCHAR(255) NOT NULL
);\n\n""")
 
    f.write("""CREATE TABLE Genre (
    genre_id VARCHAR(36) PRIMARY KEY,
    genre_name VARCHAR(100) NOT NULL
);\n\n""")
 
    f.write("""CREATE TABLE Track (
    track_id VARCHAR(50) PRIMARY KEY,
    track_name VARCHAR(255) NOT NULL,
    duration_ms INT NOT NULL CHECK (duration_ms > 0),
    explicit TINYINT(1) NOT NULL,
    popularity INT NOT NULL CHECK (popularity BETWEEN 0 AND 100),
    time_signature INT NOT NULL,
    album_id VARCHAR(36),
    FOREIGN KEY (album_id) REFERENCES Album(album_id)
);\n\n""")
 
    f.write("""CREATE TABLE AudioFeatures (
    track_id VARCHAR(50) PRIMARY KEY,
    danceability FLOAT CHECK (danceability BETWEEN 0.0 AND 1.0),
    energy FLOAT CHECK (energy BETWEEN 0.0 AND 1.0),
    `key` INT CHECK (`key` BETWEEN -1 AND 11),
    loudness FLOAT,
    mode TINYINT(1) CHECK (mode IN (0, 1)),
    speechiness FLOAT CHECK (speechiness BETWEEN 0.0 AND 1.0),
    acousticness FLOAT CHECK (acousticness BETWEEN 0.0 AND 1.0),
    instrumentalness FLOAT CHECK (instrumentalness BETWEEN 0.0 AND 1.0),
    liveness FLOAT CHECK (liveness BETWEEN 0.0 AND 1.0),
    valence FLOAT CHECK (valence BETWEEN 0.0 AND 1.0),
    tempo FLOAT,
    FOREIGN KEY (track_id) REFERENCES Track(track_id)
);\n\n""")
 
    f.write("""CREATE TABLE User (
    user_id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);\n\n""")
 
    f.write("""CREATE TABLE Playlist (
    playlist_id VARCHAR(36) PRIMARY KEY,
    playlist_name VARCHAR(255) NOT NULL,
    playlist_type ENUM('public', 'private') NOT NULL,
    user_id VARCHAR(36),
    FOREIGN KEY (user_id) REFERENCES User(user_id)
);\n\n""")
 
    f.write("""CREATE TABLE PublicPlaylist (
    playlist_id VARCHAR(36) PRIMARY KEY,
    description TEXT,
    FOREIGN KEY (playlist_id) REFERENCES Playlist(playlist_id)
);\n\n""")
 
    f.write("""CREATE TABLE PrivatePlaylist (
    playlist_id VARCHAR(36) PRIMARY KEY,
    FOREIGN KEY (playlist_id) REFERENCES Playlist(playlist_id)
);\n\n""")
 
    f.write("""CREATE TABLE TrackArtist (
    track_id VARCHAR(50),
    artist_id VARCHAR(36),
    PRIMARY KEY (track_id, artist_id),
    FOREIGN KEY (track_id) REFERENCES Track(track_id),
    FOREIGN KEY (artist_id) REFERENCES Artist(artist_id)
);\n\n""")
 
    f.write("""CREATE TABLE TrackGenre (
    track_id VARCHAR(50),
    genre_id VARCHAR(36),
    PRIMARY KEY (track_id, genre_id),
    FOREIGN KEY (track_id) REFERENCES Track(track_id),
    FOREIGN KEY (genre_id) REFERENCES Genre(genre_id)
);\n\n""")
 
    f.write("""CREATE TABLE PlaylistTrack (
    playlist_id VARCHAR(36),
    track_id VARCHAR(50),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    position INT CHECK (position > 0),
    PRIMARY KEY (playlist_id, track_id),
    FOREIGN KEY (playlist_id) REFERENCES Playlist(playlist_id),
    FOREIGN KEY (track_id) REFERENCES Track(track_id)
);\n\n""")
 
    write_inserts(f, 'Album', album_df, ['album_id', 'album_name'])
    write_inserts(f, 'Artist', artist_df, ['artist_id', 'artist_name'])
    write_inserts(f, 'Genre', genre_df, ['genre_id', 'genre_name'])
    write_inserts(f, 'Track', track_df, ['track_id', 'track_name', 'duration_ms', 'explicit', 'popularity', 'time_signature', 'album_id'])
    write_inserts(f, 'AudioFeatures', audio_df, ['track_id', 'danceability', 'energy', 'key', 'loudness', 'mode', 'speechiness', 'acousticness', 'instrumentalness', 'liveness', 'valence', 'tempo'])
    write_inserts(f, 'TrackArtist', track_artist_df, ['track_id', 'artist_id'])
    write_inserts(f, 'TrackGenre', track_genre_df, ['track_id', 'genre_id'])
 
print(f"  CSVs guardados en: {OUTPUT_DIR}/")
print(f"  SQL guardado en:   {SQL_FILE}")
 