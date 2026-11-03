# Offline, disposable local fixture. Runner verifies existing image IDs before use.
# One filesystem layer avoids vfs-driver space amplification in the cloud executor.
FROM wordpress:6.8.3-php8.1-apache
COPY rootfs /
ENTRYPOINT ["/usr/bin/python3","-I","-B","/fixture/tests/recovery/restore_fixture.py"]
