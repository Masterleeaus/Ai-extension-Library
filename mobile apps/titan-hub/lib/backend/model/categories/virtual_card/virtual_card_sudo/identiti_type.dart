import '../../../../../widgets/payment_link/custom_drop_down.dart';

class IdentityTypeModel implements DropdownModel {
  final String label, value;
  IdentityTypeModel({required this.label, required this.value});

  @override
  String get title => label;
}
