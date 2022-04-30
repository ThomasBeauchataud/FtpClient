# FtpClient

This library allow developers to create ftp clients services to be able to manage multiple ftp connection with a common
abstraction for all protocols (FTP, SFTP, SCP)

- [Client construction](#client-construction)
- [Client usage](#client-usage)

## Client construction

There is two ways of creating a client : 
- By creating the service yourself
```
$ftpClient = new FtpClient('localhost', 'root', 'password');
```
- Or by using the service factory, allowing you to set a default configuration for all created clients by this factory
```
$ftpClientFactory = new FtpClientFactory(['host' => 'localhost'];

$config = ['user' => 'root', 'password' => 'password'];
$ftpClient1 = $this->ftpClientFactory->createClient($config);

// Overwrite the default host
$config = ['host' => 'www.mydomain.com', 'user' => 'root', 'password' => 'password'];
$ftpClient2 = $this->ftpClientFactory->createClient($config);
```

Below are the different clients/factories for the different protocols :
- FTP
```
$ftpClient = (new FtpClientFactory())->createClient(...);
$ftpClient = new FtpClient(...);
```
- SFTP
```
$sftpClient = (new SftpClientFactory())->createClient(...);
$sftpClient = new SftpClient(...);
```
>You can use both password or public key for credentials
> - ```new SftpClient('localhost', 'root', 'password')```
> - ```new SftpClient('localhost', 'root', ['publicKey' => 'your-key', 'privateKey' => 'your-key', 'passpharse' => null])```
- SCP     
```
$scpClient = (new ScpClientFactory())->createClient(...);
$scpClient = new ScpClient(...);
```
>You can use both password or public key for credentials
> - ```new ScpClient('localhost', 'root', 'password')```
> - ```new ScpClient('localhost', 'root', ['publicKey' => 'your-key', 'privateKey' => 'your-key', 'passpharse' => null])```

## Client usage

Once your have your client you can use the following methods for the followings actions whatever is your client behind
the interface.

- **download** : download a file from the remote ftp server to your local server

```
$ftpClient->download('remote_file.txt', '/tmp/local_file.txt');
```

- **upload** : upload a file from your local server to the ftp server

```
$ftpClient->upload('/tmp/local_file.txt', 'remote_file.txt');
```

- **scan** : scan all remotes files and remotes directories in the given remote directory

```
$list = $ftpClient->scan('.');
```

- **exists** : check if a remote file or a remote directory exists

```
$exists = $ftpClient->exists('remote_file.txt');
```

- **delete** : delete a remote file

```
$ftpClient->delete('remote_file.txt');
```

- **mkdir** : create a remote directory

```
$ftpClient->mkdir('/demo');
```

- **rename** : rename a remote file

```
$ftpClient->rename('remote_file.txt', 'new_remote_file.txt');
```