**bchecker-srv** - SaaS with studypet/bracket-checker usage

Usege: 
```bash
bchecker-srv config.yaml
```
config.yaml example
```yaml
ip: 0.0.0.0
port: 10001
```
Also you can use docker image for start bchacker server
```bash
docker pull zanin/bchecker-srv
docker run -p 10001:10001 zanin/bchecker-srv
```

