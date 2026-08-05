"""Temporary bridge to the runner's real pip package for donor import."""
from pathlib import Path
import sysconfig

_real_pip = Path(sysconfig.get_path("purelib")) / "pip"
exec(
    compile((_real_pip / "__init__.py").read_bytes(), str(_real_pip / "__init__.py"), "exec"),
    globals(),
    globals(),
)
__path__ = [str(Path(__file__).parent), str(_real_pip)]
