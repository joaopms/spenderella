let
  nixpkgs = fetchTarball "https://github.com/NixOS/nixpkgs/tarball/nixos-24.05";
  pkgs = import nixpkgs { config = {}; overlays = []; };
in
  pkgs.mkShellNoCC {
    packages = with pkgs; [
      php # 8.2
      php82Packages.composer
    ];

    shellHook = ''
      alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
    '';
  }
