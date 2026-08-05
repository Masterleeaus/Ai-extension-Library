import '../widgets/payment_link/custom_drop_down.dart';

class IdTypeModel implements DropdownModel {
  final String mId;
  final String name;

  IdTypeModel(this.mId, this.name);

  @override
  String get title => name;
}
