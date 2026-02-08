CREATE EXTENSION IF NOT EXISTS pg_trgm;  -- Trigram matching for fuzzy search
CREATE EXTENSION IF NOT EXISTS unaccent; -- Remove accents from text
CREATE EXTENSION IF NOT EXISTS btree_gin; -- Better indexing for multiple columns
