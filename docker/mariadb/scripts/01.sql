CREATE DATABASE IF NOT EXISTS llphant;
GRANT ALL ON *.* TO 'root'@'%';

CREATE TABLE IF NOT EXISTS test_place (
                                          id SERIAL PRIMARY KEY,
                                          content text,
                                          type text,
                                          sourcetype text,
                                          sourcename text,
                                          embedding vector(3072) not null,
                                          chunknumber int,
                                          VECTOR INDEX (embedding)
);
CREATE TABLE IF NOT EXISTS test_doc (
                                        id SERIAL PRIMARY KEY,
                                        content text,
                                        type text,
                                        sourcetype text,
                                        sourcename text,
                                        embedding vector(1024) not null,
                                        chunknumber int,
                                        VECTOR INDEX (embedding)
);
